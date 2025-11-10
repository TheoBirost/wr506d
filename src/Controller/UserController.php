<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/api/users/{id}/role', name: 'update_user_role', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateRole(
        int $id,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $userRepo->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $newRole = $data['role'] ?? 'ROLE_USER';

        // Un seul rôle : le plus fort
        $user->setRoles([$newRole]);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'roles' => [$newRole],
            'message' => 'Rôle mis à jour avec succès'
        ]);
    }
}
