<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{

    #[Route('/products', name: 'app_product_list')]
    public function listProducts(): Response
    {
        return $this->render('product/index.html.twig', [
            'content' => 'Liste des produits',
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_view')]
    public function viewProduct(int $id): Response
    {
        return $this->render('product/view.html.twig', [
            'content' => 'Liste des produits',
            'view' => "Affichage du produit $id",
        ]);
    }

}


