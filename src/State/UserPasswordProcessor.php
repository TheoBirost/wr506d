<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Hache `plainPassword` avant que Doctrine n'enregistre l'utilisateur.
 *
 * Pourquoi un processeur plutôt qu'un écouteur Doctrine : l'écouteur existant
 * n'est branché que sur `prePersist`, donc uniquement à la création. Le
 * déplacer ou l'étendre à `preUpdate` ne suffirait pas — `preUpdate` n'est
 * déclenché que si Doctrine détecte un changement sur un champ **mappé**, et
 * `plainPassword` n'en est pas un. Une requête ne modifiant que le mot de passe
 * ne produirait aucun changeset : l'écouteur ne serait jamais appelé et le
 * changement serait silencieusement perdu.
 *
 * Le processeur s'exécute avant la persistance, quelle que soit l'opération,
 * et ne dépend d'aucun calcul de changeset.
 *
 * L'écouteur `UserPasswordListener` est conservé : il couvre les créations
 * hors API (fixtures, commandes console). Les deux sont idempotents — celui
 * qui passe en second trouve `plainPassword` déjà vidé.
 */
final class UserPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): mixed {
        if ($data instanceof User) {
            $plainPassword = $data->getPlainPassword();

            if (null !== $plainPassword && '' !== trim($plainPassword)) {
                $data->setPassword($this->hasher->hashPassword($data, $plainPassword));
            }

            // Vidé dans tous les cas : le mot de passe en clair ne doit pas
            // survivre à la requête, y compris s'il n'a pas été utilisé.
            $data->setPlainPassword(null);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
