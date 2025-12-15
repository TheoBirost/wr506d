<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-api-key',
    description: 'Génère une clé API pour un utilisateur',
)]
class GenerateApiKeyCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        // Recherche de l'utilisateur
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Utilisateur avec l\'email "%s" introuvable', $email));
            return Command::FAILURE;
        }

        // Génération de la clé API
        // 1. random_bytes - 32 octets
        $randomBytes = random_bytes(32);

        // 2. bin2hex du random_bytes généré
        $apiKey = bin2hex($randomBytes);

        // 3. hash en sha256 sur le résultat du bin2hex
        $apiKeyHash = hash('sha256', $apiKey);

        // Extraction du préfixe (16 premiers caractères)
        $apiKeyPrefix = substr($apiKey, 0, 16);

        // Mise à jour de l'utilisateur
        $user->setApiKeyHash($apiKeyHash);
        $user->setApiKeyPrefix($apiKeyPrefix);
        $user->setApiKeyEnabled(true);
        $user->setApiKeyCreatedAt(new \DateTimeImmutable());
        $user->setApiKeyLastUsedAt(null);

        $this->entityManager->flush();

        // Affichage des informations
        $io->success('Clé API générée avec succès !');
        $io->section('Informations de la clé API');
        $io->table(
            ['Information', 'Valeur'],
            [
                ['Utilisateur', $user->getEmail()],
                ['Préfixe', $apiKeyPrefix],
                ['Clé complète', $apiKey],
                ['Statut', $user->isApiKeyEnabled() ? 'Activée' : 'Désactivée'],
                ['Date de création', $user->getApiKeyCreatedAt()->format('Y-m-d H:i:s')],
            ]
        );

        $io->warning('⚠️  ATTENTION : Cette clé ne sera plus jamais affichée. Copiez-la maintenant !');
        $io->note('Utilisez cette clé dans le header HTTP : X-API-Key: ' . $apiKey);

        return Command::SUCCESS;
    }
}
