<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/2fa')]
#[IsGranted('ROLE_USER')]
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
    #[Route('/setup', name: 'app_2fa_setup', methods: ['POST'])]
    public function setup(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        // Génération du secret TOTP
        $secret = $this->twoFactorService->generateSecret();
        $user->setTwoFactorSecret($secret);
        // ON FORCE À FALSE pour l'instant
        $user->setTwoFactorEnabled(false);

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
    }

    /**
     * Étape 2 : Enable - Vérification et activation du 2FA
     */
    #[Route('/enable', name: 'app_2fa_enable', methods: ['POST'])]
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

        // Vérifier le code avant de désactiver
        if (!$this->twoFactorService->verifyCode($user, $code)) {
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
}
