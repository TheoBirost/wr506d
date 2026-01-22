<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'get_categories', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepo): JsonResponse
    {
        $categories = $categoryRepo->findAllWithMoviesCount();

        $data = array_map(function ($category) {
            return [
            'id' => $category[0]->getId(),
            'name' => $category[0]->getName(),
            'moviesCount' => (int) $category['moviesCount'],
            ];
        }, $categories);

        return new JsonResponse($data);
    }
}
