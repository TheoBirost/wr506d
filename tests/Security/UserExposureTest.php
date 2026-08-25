<?php

namespace App\Tests\Security;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use App\Entity\MediaObject;
use App\Entity\Review;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

/**
 * Garde-fous sur l'exposition des données sensibles par l'API.
 *
 * Ces tests existent à cause d'une fuite réelle : `GET /api/users` répondait
 * publiquement et renvoyait l'empreinte bcrypt du mot de passe de chaque
 * compte. La cause tenait en deux oublis — une opération déclarée sans
 * `security`, et sans groupe de sérialisation, ce qui pousse API Platform à
 * sérialiser tous les accesseurs publics de l'entité.
 *
 * Les deux oublis étant invisibles à la lecture (rien ne « casse »), ils sont
 * vérifiés ici. Aucune base de données n'est nécessaire : on interroge les
 * métadonnées, pas des enregistrements.
 */
final class UserExposureTest extends KernelTestCase
{
    /** Champs qui ne doivent porter aucun groupe, ni en lecture ni en écriture. */
    private const NEVER_SERIALIZED = [
        'password',
        'apiKey',
        'twoFactorSecret',
        'twoFactorBackupCodes',
    ];

    /**
     * Champs légitimes en entrée mais jamais en sortie.
     * `plainPassword` appartient à `user:write` — c'est le champ d'inscription —
     * et doit rester absent de tout groupe de lecture.
     */
    private const WRITE_ONLY = ['plainPassword'];

    /**
     * Toute opération exposée doit porter une expression `security`.
     */
    #[DataProvider('guardedResources')]
    public function testEveryOperationDeclaresSecurity(string $resourceClass): void
    {
        self::bootKernel();

        /** @var ResourceMetadataCollectionFactoryInterface $factory */
        $factory = self::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class);

        $checked = 0;

        foreach ($factory->create($resourceClass) as $resource) {
            foreach ($resource->getOperations() as $name => $operation) {
                $security = $operation->getSecurity();

                self::assertNotEmpty(
                    $security,
                    sprintf(
                        "L'opération « %s » de %s n'a pas d'expression security : "
                        . "elle hérite alors de la règle générique du pare-feu, "
                        . 'qui autorise les GET publics.',
                        $name,
                        $resourceClass
                    )
                );

                ++$checked;
            }
        }

        self::assertGreaterThan(0, $checked, 'Aucune opération inspectée.');
    }

    /**
     * Toute opération de lecture doit restreindre la sérialisation par groupes.
     *
     * Sans `normalizationContext`, API Platform sérialise tout accesseur public
     * — y compris getPassword().
     */
    #[DataProvider('guardedResources')]
    public function testEveryReadOperationRestrictsSerializationGroups(string $resourceClass): void
    {
        self::bootKernel();

        /** @var ResourceMetadataCollectionFactoryInterface $factory */
        $factory = self::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class);

        foreach ($factory->create($resourceClass) as $resource) {
            foreach ($resource->getOperations() as $name => $operation) {
                $groups = $operation->getNormalizationContext()['groups'] ?? null;

                self::assertNotEmpty(
                    $groups,
                    sprintf(
                        "L'opération « %s » de %s n'impose aucun groupe de "
                        . 'sérialisation : la réponse contiendra tous les '
                        . 'accesseurs publics de l\'entité.',
                        $name,
                        $resourceClass
                    )
                );
            }
        }
    }

    /**
     * Les champs sensibles ne portent aucun groupe de sérialisation.
     *
     * Ceinture par-dessus les bretelles du test précédent : même si un groupe
     * était ajouté par erreur à une opération, ces propriétés resteraient
     * hors des réponses.
     */
    public function testSensitiveFieldsBelongToNoSerializationGroup(): void
    {
        self::bootKernel();

        /** @var ClassMetadataFactoryInterface $factory */
        $factory = self::getContainer()->get('serializer.mapping.class_metadata_factory');
        $attributes = $factory->getMetadataFor(User::class)->getAttributesMetadata();

        foreach (self::NEVER_SERIALIZED as $field) {
            if (!isset($attributes[$field])) {
                continue;
            }

            self::assertSame(
                [],
                $attributes[$field]->getGroups(),
                sprintf(
                    'La propriété « %s » de User porte un groupe de '
                    . "sérialisation : elle peut sortir dans une réponse de l'API.",
                    $field
                )
            );
        }

        foreach (self::WRITE_ONLY as $field) {
            if (!isset($attributes[$field])) {
                continue;
            }

            $readGroups = array_filter(
                $attributes[$field]->getGroups(),
                static fn (string $group): bool => str_contains($group, ':read')
            );

            self::assertSame(
                [],
                $readGroups,
                sprintf(
                    'La propriété « %s » de User appartient à un groupe de '
                    . 'lecture : elle ressortirait en clair dans les réponses.',
                    $field
                )
            );
        }
    }

    /**
     * La liste complète des comptes reste réservée aux administrateurs.
     *
     * C'est l'opération exacte qui fuyait.
     */
    public function testUserCollectionIsAdminOnly(): void
    {
        self::bootKernel();

        /** @var ResourceMetadataCollectionFactoryInterface $factory */
        $factory = self::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class);

        $found = false;

        foreach ($factory->create(User::class) as $resource) {
            foreach ($resource->getOperations() as $operation) {
                if (!$operation instanceof CollectionOperationInterface) {
                    continue;
                }
                if ('GET' !== $operation->getMethod()) {
                    continue;
                }

                $found = true;
                self::assertStringContainsString(
                    'ROLE_ADMIN',
                    (string) $operation->getSecurity(),
                    'GET /api/users doit rester réservé aux administrateurs.'
                );
            }
        }

        self::assertTrue($found, "L'opération GetCollection de User est introuvable.");
    }

    /** @return iterable<string, array{class-string}> */
    public static function guardedResources(): iterable
    {
        yield 'User' => [User::class];
        yield 'Review' => [Review::class];
        yield 'MediaObject' => [MediaObject::class];
    }
}
