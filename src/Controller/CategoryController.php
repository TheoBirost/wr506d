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
// Récupère toutes les catégories avec le nombre de films liés
$categories = $categoryRepo->findAllWithMoviesCount();

// Si tu veux renvoyer un JSON “propre”, on transforme les objets
$data = array_map(function($category) {
// $category[0] = l'entité Category, $category['moviesCount'] = count
return [
'id' => $category[0]->getId(),
'name' => $category[0]->getName(),
'moviesCount' => (int) $category['moviesCount'],
];
}, $categories);

return new JsonResponse($data);
}
}
