<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/me/api-key')]
#[IsGranted('ROLE_USER')]
class ApiKeyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Générer une nouvelle clé API
     */
    #[Route('', name: 'api_key_generate', methods: ['POST'])]
    public function generate(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $apiKeyObject = $user->getApiKey();

        // Génération de la clé API
        $randomBytes = random_bytes(32);
        $apiKey = bin2hex($randomBytes);
        $apiKeyHash = hash('sha256', $apiKey);
        $apiKeyPrefix = substr($apiKey, 0, 16);

        // Mise à jour de l'objet ApiKey
        $apiKeyObject->setHash($apiKeyHash);
        $apiKeyObject->setPrefix($apiKeyPrefix);
        $apiKeyObject->setEnabled(true);
        $apiKeyObject->setCreatedAt(new DateTimeImmutable());
        $apiKeyObject->setLastUsedAt(null);

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Clé API générée avec succès',
            'apiKey' => $apiKey,
            'prefix' => $apiKeyPrefix,
            'enabled' => true,
            'createdAt' => $apiKeyObject->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'warning' => 'Cette clé ne sera plus jamais affichée. Copiez-la maintenant !'
        ], Response::HTTP_CREATED);
    }

    /**
     * Voir le statut de la clé API
     */
    #[Route('', name: 'api_key_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $apiKeyObject = $user->getApiKey();

        if (!$apiKeyObject->getHash()) {
            return $this->json([
                'message' => 'Aucune clé API générée',
                'hasKey' => false
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'hasKey' => true,
            'prefix' => $apiKeyObject->getPrefix(),
            'enabled' => $apiKeyObject->isEnabled(),
            'createdAt' => $apiKeyObject->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'lastUsedAt' => $apiKeyObject->getLastUsedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Activer/désactiver la clé API
     */
    #[Route('', name: 'api_key_toggle', methods: ['PATCH'])]
    public function toggle(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $apiKeyObject = $user->getApiKey();

        if (!$apiKeyObject->getHash()) {
            return $this->json([
                'message' => 'Aucune clé API générée'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['enabled']) || !is_bool($data['enabled'])) {
            return $this->json([
                'message' => 'Le paramètre "enabled" est requis et doit être un booléen'
            ], Response::HTTP_BAD_REQUEST);
        }

        $apiKeyObject->setEnabled($data['enabled']);
        $this->entityManager->flush();

        return $this->json([
            'message' => $data['enabled'] ? 'Clé API activée' : 'Clé API désactivée',
            'enabled' => $apiKeyObject->isEnabled()
        ]);
    }

    /**
     * Révoquer la clé API
     */
    #[Route('', name: 'api_key_revoke', methods: ['DELETE'])]
    public function revoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $apiKeyObject = $user->getApiKey();

        if (!$apiKeyObject->getHash()) {
            return $this->json([
                'message' => 'Aucune clé API à révoquer'
            ], Response::HTTP_NOT_FOUND);
        }

        // Suppression complète de la clé
        $apiKeyObject->setHash(null);
        $apiKeyObject->setPrefix(null);
        $apiKeyObject->setEnabled(false);
        $apiKeyObject->setCreatedAt(null);
        $apiKeyObject->setLastUsedAt(null);

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Clé API révoquée avec succès'
        ]);
    }
}
