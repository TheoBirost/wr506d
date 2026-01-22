<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DocumentationTest extends WebTestCase
{
    public function testApiDocumentationIsAccessible(): void
    {
        // Créer une requête HTTP vers l'URL de la documentation
        $client = static::createClient();

        // Vérifiez l'accès à la documentation
        $client->request('GET', '/api/docs');  // Remplacez '/docs' si vous avez un autre chemin

        // Vérifiez que la réponse a un code 200 (OK)
        $this->assertResponseIsSuccessful();

        // Si vous utilisez Swagger, vous pouvez aussi vérifier que la page contient une certaine balise HTML
        // Exemple pour vérifier si Swagger est présent dans le contenu :
        //$this->assertStringContainsString('SwaggerUI', $client->getResponse()->getContent());
    }
}
