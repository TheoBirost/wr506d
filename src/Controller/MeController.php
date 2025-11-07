<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MeController extends AbstractController
{
    #[Route('/api/me', name: 'get_current_user', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté'], 401);
        }


        $photoUrl = null;
        if ($user->getPhoto() && $user->getPhoto()->filePath) {
            $photoUrl = '/media/images/' . $user->getPhoto()->filePath;
        }

        $userData = [
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'dob' => $user->getDob(),
            'roles' => $user->getRoles(),
            'photo' => $photoUrl,
        ];

        return new JsonResponse($userData);
    }
}
