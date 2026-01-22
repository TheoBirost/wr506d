<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\ActorRepository;
use App\Repository\MovieRepository;
use App\Repository\CategoryRepository;
use App\Repository\DirectorRepository;

#[AsCommand(
    name: 'app:count-command',
    description: 'Affiche le nombre d’acteurs, de films et de catégories dans la base de données',
)]
class CountCommand extends Command
{
    private ActorRepository $actorRepository;
    private MovieRepository $movieRepository;
    private CategoryRepository $categoryRepository;

    private DirectorRepository $directorRepository;


    public function __construct(
        ActorRepository $actorRepository,
        MovieRepository $movieRepository,
        CategoryRepository $categoryRepository,
        DirectorRepository $directorRepository
    ) {
        parent::__construct();
        $this->actorRepository = $actorRepository;
        $this->movieRepository = $movieRepository;
        $this->categoryRepository = $categoryRepository;
        $this->directorRepository = $directorRepository;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);

        $actorCount = $this->actorRepository->count([]);
        $movieCount = $this->movieRepository->count([]);
        $categoryCount = $this->categoryRepository->count([]);
        $directorCount = $this->directorRepository->count([]);

        $mediaDir = __DIR__ . '/../../public/media/images';
        $imageExtensions = ['jpg', 'jpeg', 'png'];
        $imageCount = 0;
        $totalSize = 0;
        if (is_dir($mediaDir)) {
            $files = scandir($mediaDir);
            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $imageExtensions)) {
                    $imageCount++;
                    $filePath = $mediaDir . $file;
                    if (is_file($filePath)) {
                        $totalSize += filesize($filePath);
                    }
                }
            }
        }

        $sizeStr = $totalSize . ' octets';
        if ($totalSize > 1024) {
            $sizeStr = round($totalSize / 1024, 2) . ' Ko';
        }
        if ($totalSize > 1048576) {
            $sizeStr = round($totalSize / 1048576, 2) . ' Mo';
        }

        $ioStyle->info('Nombre d’acteurs dans la base de données : ' . $actorCount);
        $ioStyle->info('Nombre de films dans la base de données : ' . $movieCount);
        $ioStyle->info('Nombre de catégories dans la base de données : ' . $categoryCount);
        $ioStyle->info('Nombre de réalisateurs dans la base de données : ' . $directorCount);
        $ioStyle->info('Nombre d’images dans public/media/images : ' . $imageCount);
        $ioStyle->info('Poids total des images dans public/media/images : ' . $sizeStr);

        return Command::SUCCESS;
    }
}
