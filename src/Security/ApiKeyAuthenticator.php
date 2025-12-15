<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApiKeyAuthenticator extends AbstractAuthenticator
{
    // Le préfixe est composé de 16 caractères.
    private const PREFIX_LENGTH = 16;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get('X-API-KEY');

        if (null === $apiKey || '' === $apiKey) {
            throw new CustomUserMessageAuthenticationException('No API key provided');
        }

        // 1. Extraire le préfixe de la clé fournie
        $apiKeyPrefix = substr($apiKey, 0, self::PREFIX_LENGTH);
        if (strlen($apiKeyPrefix) < self::PREFIX_LENGTH) {
            throw new CustomUserMessageAuthenticationException('Invalid API key format');
        }

        // 2. Rechercher l'utilisateur par préfixe
        $user = $this->userRepository->findOneBy(['apiKey.prefix' => $apiKeyPrefix]);

        if (null === $user) {
            throw new CustomUserMessageAuthenticationException('Invalid API key');
        }

        if (!$user->getApiKey()->isEnabled()) {
            throw new CustomUserMessageAuthenticationException('API key is disabled');
        }

        // 3. Vérifier le hash de la clé complète
        $apiKeyHash = hash('sha256', $apiKey);
        if (!hash_equals($user->getApiKey()->getHash(), $apiKeyHash)) {
            throw new CustomUserMessageAuthenticationException('Invalid API key');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Mettre à jour la date de dernière utilisation
        $user = $token->getUser();
        if ($user instanceof User) {
            $user->getApiKey()->updateLastUsedAt();
            $this->entityManager->flush();
        }

        // En cas de succès, on laisse la requête continuer
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ], Response::HTTP_UNAUTHORIZED);
    }
}
