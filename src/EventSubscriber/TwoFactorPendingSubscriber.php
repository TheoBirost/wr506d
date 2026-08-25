<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Cantonne le jeton intermédiaire de la double authentification.
 *
 * Après un mot de passe correct sur un compte protégé par 2FA,
 * AuthenticationSuccessListener renvoie un JWT portant le claim
 * `2fa_pending`. Ce jeton est un JWT parfaitement valide : le pare-feu
 * l'acceptait donc sur TOUTES les routes, et il suffisait de l'envoyer à
 * /api/me ou à n'importe quel endpoint ROLE_USER pour agir sans jamais saisir
 * le code — la double authentification ne protégeait plus rien.
 *
 * Ce souscripteur n'autorise un tel jeton que sur la route de vérification.
 */
final class TwoFactorPendingSubscriber implements EventSubscriberInterface
{
    /** Seule route accessible avec un jeton en attente de vérification. */
    private const ALLOWED_ROUTE = 'app_2fa_login_verify';

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité 4 : après le pare-feu (8), qui a déjà rempli le TokenStorage,
        // et avant la résolution du contrôleur.
        return [KernelEvents::REQUEST => ['onKernelRequest', 4]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->isMethod('OPTIONS')) {
            return;
        }

        if (self::ALLOWED_ROUTE === $request->attributes->get('_route')) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        try {
            $payload = $this->jwtManager->decode($token);
        } catch (\Throwable) {
            // Authentification par clé API, ou jeton non décodable ici :
            // ce n'est pas à ce souscripteur d'en juger.
            return;
        }

        if (!\is_array($payload) || true !== ($payload['2fa_pending'] ?? null)) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => 'two_factor_required',
            'message' => "Validez le code de votre application d'authentification pour terminer la connexion.",
        ], 403));
    }
}
