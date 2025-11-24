<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MeController extends AbstractController
{
    #[Route('/api/me', name: 'get_current_user', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non connecté'], 401);
        }

        $photoUrl = null;
        if ($user->getPhoto() && $user->getPhoto()->filePath) {
            $photoUrl = '/media/images/' . $user->getPhoto()->filePath;
        }

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'dob' => $user->getDob(),
            'roles' => $user->getRoles(),
            'photo' => $photoUrl,
        ];

        return new JsonResponse($userData);
    }
}
