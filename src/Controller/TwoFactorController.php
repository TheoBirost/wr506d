<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/2fa')]
class TwoFactorController extends AbstractController
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Étape 1 : Setup - Génération du QR code
     */
    #[Route('/setup', name: 'app_2fa_setup', methods: ['POST', 'GET'])] // Temporairement GET pour le debug
    #[IsGranted('ROLE_USER')]
    public function setup(): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], 401);
            }

            // Nettoyage préventif : on s'assure qu'on repart de zéro
            $user->setTwoFactorEnabled(false);
            $user->setTwoFactorBackupCodes(null);

            // Génération du NOUVEAU secret TOTP
            $secret = $this->twoFactorService->generateSecret();
            $user->setTwoFactorSecret($secret);

            $this->entityManager->flush();

            // Génération du QR code
            $qrCodeDataUri = $this->twoFactorService->getQrCode($user);
            // URL de provisioning générée par le module OTP
            $provisioningUri = $this->twoFactorService->getProvisioningUri($user);

            return $this->json([
                'secret' => $secret,
                'qr_code' => $qrCodeDataUri,
                'provisioning_uri' => $provisioningUri,
                'message' => 'Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)',
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'An unexpected error occurred during 2FA setup.',
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Étape 2 : Enable - Vérification et activation du 2FA
     */
    #[Route('/enable', name: 'app_2fa_enable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        // Vérifier que le setup a été fait
        if (!$user->getTwoFactorSecret()) {
            return $this->json([
                'error' => 'Please call /api/2fa/setup first'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        if (empty($code)) {
            return $this->json(['error' => 'Code is required'], 400);
        }

        // Vérifier le code TOTP
        if (!$this->twoFactorService->verifyCode($user, $code)) {
            return $this->json(['error' => 'Invalid code'], 400);
        }

        // Générer les codes de secours
        $backupCodes = $this->twoFactorService->generateBackupCodes();
        $hashedBackupCodes = $this->twoFactorService->hashBackupCodes($backupCodes);

        // Activer le 2FA
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorBackupCodes($hashedBackupCodes);

        $this->entityManager->flush();

        return $this->json([
            'message' => '2FA enabled successfully',
            'backup_codes' => $backupCodes,
            'warning' => 'Save these backup codes in a safe place. They will not be shown again!',
        ]);
    }

    /**
     * Désactiver le 2FA
     */
    #[Route('/disable', name: 'app_2fa_disable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function disable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        if (!$user->isTwoFactorEnabled()) {
            return $this->json(['error' => '2FA is not enabled'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        if (empty($code)) {
            return $this->json(['error' => 'Code is required to disable 2FA'], 400);
        }

        // Vérifier le code avant de désactiver (TOTP ou Backup Code)
        $isTotpValid = $this->twoFactorService->verifyCode($user, $code);
        $isBackupValid = $this->twoFactorService->verifyBackupCode($user, $code);

        if (!$isTotpValid && !$isBackupValid) {
            return $this->json(['error' => 'Invalid code'], 400);
        }

        // Désactiver et supprimer toutes les données 2FA
        $user->setTwoFactorEnabled(false);
        $user->setTwoFactorSecret(null);
        $user->setTwoFactorBackupCodes(null);

        $this->entityManager->flush();

        return $this->json([
            'message' => '2FA disabled successfully'
        ]);
    }

    /**
     * Vérifier le statut du 2FA
     */
    #[Route('/status', name: 'app_2fa_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function status(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        return $this->json([
            'enabled' => $user->isTwoFactorEnabled(),
            'secret_configured' => $user->getTwoFactorSecret() !== null,
        ]);
    }

    /**
     * Étape de vérification du login 2FA
     */
    #[Route('/login/verify', name: 'app_2fa_login_verify', methods: ['POST'])]
    public function verifyLogin(Request $request, JWTTokenManagerInterface $jwtManager, TokenStorageInterface $tokenStorage): JsonResponse
    {
        try {
            // On vérifie que l'utilisateur est bien authentifié (même avec un token temporaire)
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            /** @var User $user */
            $user = $this->getUser();

            // On vérifie que le token est bien un token temporaire 2FA
            $token = $tokenStorage->getToken();
            $payload = $jwtManager->decode($token);

            if (!isset($payload['2fa_pending']) || $payload['2fa_pending'] !== true) {
                throw new AccessDeniedException('This is not a 2FA token.');
            }

            $data = json_decode($request->getContent(), true);
            $code = $data['code'] ?? '';

            if (empty($code)) {
                return $this->json(['error' => 'Code is required'], 400);
            }

            // Vérifier le code TOTP ou un code de secours
            $isTotpValid = $this->twoFactorService->verifyCode($user, $code);
            $isBackupValid = $this->twoFactorService->verifyBackupCode($user, $code);

            if (!$isTotpValid && !$isBackupValid) {
                return $this->json(['error' => 'Invalid code'], 400);
            }

            // Si un code de secours a été utilisé, on le supprime
            if ($isBackupValid) {
                $this->twoFactorService->removeBackupCode($user, $code);
                $this->entityManager->flush();
            }

            // Le code est valide, on génère le token JWT final
            // On enlève le claim '2fa_pending' pour le token final
            $finalToken = $jwtManager->create($user);

            return $this->json(['token' => $finalToken]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'An unexpected error occurred during 2FA verification.',
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
