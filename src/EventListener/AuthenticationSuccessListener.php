<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthenticationSuccessListener
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Si le 2FA est activé pour cet utilisateur
        if ($user->isTwoFactorEnabled()) {
            // On vide les données standard (le token final)
            $data = [];

            // On génère un token temporaire avec un claim spécifique "2fa_pending"
            // On ne touche pas aux rôles ici pour éviter les conflits, on ajoute juste un claim custom
            $payload = ['2fa_pending' => true];

            // On crée le token
            $token = $this->jwtManager->createFromPayload($user, $payload);

            // On renvoie ce token temporaire et un flag pour le front
            $data['token'] = $token;
            $data['2fa_required'] = true;

            $event->setData($data);
        }
    }
}
