<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Movie;
use App\Entity\Director;

class DataFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // ==== ACTORS ====
        $actorsArray = [];

        // Liste de vrais acteurs célèbres
        $famousActors = [
            ['Leonardo', 'DiCaprio'], ['Brad', 'Pitt'], ['Tom', 'Hanks'],
            ['Robert', 'De Niro'], ['Al', 'Pacino'], ['Morgan', 'Freeman'],
            ['Denzel', 'Washington'], ['Johnny', 'Depp'], ['Will', 'Smith'],
            ['Matt', 'Damon'], ['Christian', 'Bale'], ['Ryan', 'Gosling'],
            ['Scarlett', 'Johansson'], ['Meryl', 'Streep'], ['Jennifer', 'Lawrence'],
            ['Natalie', 'Portman'], ['Cate', 'Blanchett'], ['Emma', 'Stone'],
            ['Angelina', 'Jolie'], ['Kate', 'Winslet'], ['Charlize', 'Theron'],
            ['Nicole', 'Kidman'], ['Julia', 'Roberts'], ['Sandra', 'Bullock'],
            ['Marion', 'Cotillard'], ['Léa', 'Seydoux'], ['Vincent', 'Cassel'],
            ['Jean', 'Reno'], ['Omar', 'Sy'], ['Gérard', 'Depardieu']
        ];

        // Ajouter les acteurs célèbres
        foreach ($famousActors as $actorData) {
            $actor = new Actor();
            $actor->setFirstName($actorData[0]);
            $actor->setLastName($actorData[1]);
            $actor->setBio($faker->paragraph(3));
            $actor->setDob($faker->dateTimeBetween('-80 years', '-20 years'));

            // 15% de chance d'être décédé
            if ($faker->boolean(15)) {
                $dob = $actor->getDob();
                $actor->setDod($faker->dateTimeBetween($dob, 'now'));
            }

            $actorsArray[] = $actor;
            $manager->persist($actor);
        }

        // Ajouter 110 acteurs générés aléatoirement (total: 140)
        for ($i = 0; $i < 110; $i++) {
            $actor = new Actor();
            $actor->setFirstName($faker->firstName());
            $actor->setLastName($faker->lastName());
            $actor->setBio($faker->paragraph(2));
            $actor->setDob($faker->dateTimeBetween('-70 years', '-18 years'));

            if ($faker->boolean(20)) {
                $dob = $actor->getDob();
                $actor->setDod($faker->dateTimeBetween($dob, 'now'));
            }

            $actorsArray[] = $actor;
            $manager->persist($actor);
        }

        // ==== DIRECTORS ====
        $directorsArray = [];

        // Quelques réalisateurs célèbres
        $famousDirectors = [
            ['Christopher', 'Nolan'], ['Steven', 'Spielberg'], ['Quentin', 'Tarantino'],
            ['Martin', 'Scorsese'], ['Francis Ford', 'Coppola'], ['Ridley', 'Scott'],
            ['Denis', 'Villeneuve'], ['Wes', 'Anderson'], ['David', 'Fincher'],
            ['James', 'Cameron'], ['Peter', 'Jackson'], ['Tim', 'Burton'],
            ['Clint', 'Eastwood'], ['Spike', 'Lee'], ['Alfonso', 'Cuarón'],
            ['Guillermo', 'Del Toro'], ['Jean-Luc', 'Godard'], ['François', 'Truffaut'],
            ['Luc', 'Besson'], ['Jacques', 'Audiard']
        ];

        // Ajouter les réalisateurs célèbres
        foreach ($famousDirectors as $directorData) {
            $director = new Director();
            $director->setFirstname($directorData[0]);
            $director->setLastname($directorData[1]);
            $director->setDob($faker->dateTimeBetween('-80 years', '-40 years'));

            // 25% de chance d'être décédé
            if ($faker->boolean(25)) {
                $dob = $director->getDob();
                $director->setDod($faker->dateTimeBetween($dob, 'now'));
            }

            $directorsArray[] = $director;
            $manager->persist($director);
        }

        // Ajouter 30 réalisateurs générés aléatoirement (total: 50)
        for ($i = 0; $i < 30; $i++) {
            $director = new Director();
            $director->setFirstname($faker->firstName());
            $director->setLastname($faker->lastName());
            $director->setDob($faker->dateTimeBetween('-85 years', '-30 years'));

            if ($faker->boolean(20)) {
                $dob = $director->getDob();
                $director->setDod($faker->dateTimeBetween($dob, 'now'));
            }

            $directorsArray[] = $director;
            $manager->persist($director);
        }

        // ==== CATEGORIES ====
        $categoryNames = [
            'Action', 'Aventure', 'Animation', 'Comédie', 'Crime',
            'Documentaire', 'Drame', 'Fantastique', 'Horreur', 'Mystère',
            'Romance', 'Science-Fiction', 'Thriller', 'Western', 'Guerre',
            'Musical', 'Biographie', 'Histoire', 'Sport', 'Film Noir'
        ];

        $categoryArray = [];
        foreach ($categoryNames as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $categoryArray[$categoryName] = $category;
            $manager->persist($category);
        }

        // ==== MOVIES ====
        // Quelques films célèbres
        $famousMovies = [
            'The Shawshank Redemption', 'The Godfather', 'The Dark Knight',
            'Pulp Fiction', 'Forrest Gump', 'Inception', 'Fight Club',
            'The Matrix', 'Goodfellas', 'The Lord of the Rings',
            'Star Wars', 'Jurassic Park', 'Titanic', 'Avatar',
            'The Silence of the Lambs', 'Saving Private Ryan', 'Interstellar',
            'The Green Mile', 'Se7en', 'The Prestige', 'Gladiator',
            'The Departed', 'The Lion King', 'Back to the Future',
            'Die Hard', 'Alien', 'Blade Runner', 'The Terminator',
            'Schindler\'s List', 'Casablanca', 'Citizen Kane',
            'Le Fabuleux Destin d\'Amélie Poulain', 'Intouchables',
            'La Haine', 'Les Choristes', 'Le Dîner de Cons'
        ];

        // Ajouter les films célèbres
        foreach ($famousMovies as $movieTitle) {
            $movie = new Movie();
            $movie->setName($movieTitle);
            $movie->setDescription($faker->paragraph(4));
            $movie->setDuration($faker->numberBetween(80, 240));
            $movie->setReleaseDate($faker->dateTimeBetween('-50 years', 'now'));
            $movie->setNbEntries($faker->numberBetween(100000, 15000000));
            $movie->setBudget($faker->randomFloat(2, 1000000, 250000000));

            // URL fictive
            $slug = strtolower(str_replace(' ', '-', $movieTitle));
            $movie->setUrl("https://www.imdb.com/title/{$slug}");

            // Ajouter 1 à 3 catégories aléatoires
            $randomCategories = $faker->randomElements($categoryArray, $faker->numberBetween(1, 3));
            foreach ($randomCategories as $category) {
                $movie->addCategory($category);
            }

            // Ajouter 3 à 8 acteurs aléatoires
            $randomActors = $faker->randomElements($actorsArray, $faker->numberBetween(3, 8));
            foreach ($randomActors as $actor) {
                $movie->addActor($actor);
            }

            // Ajouter un réalisateur aléatoire
            $movie->setDirector($faker->randomElement($directorsArray));

            $manager->persist($movie);
        }

        // Ajouter 164 films générés aléatoirement (total: 199)
        for ($i = 0; $i < 164; $i++) {
            $movie = new Movie();

            // Générer un titre de film
            $titleFormats = [
                $faker->words(2, true),
                'The ' . $faker->word(),
                $faker->firstName() . ' ' . $faker->lastName(),
                'Le ' . $faker->word() . ' ' . $faker->word(),
                $faker->word() . ' ' . $faker->word()
            ];
            $movie->setName(ucwords($faker->randomElement($titleFormats)));

            $movie->setDescription($faker->paragraph($faker->numberBetween(2, 5)));
            $movie->setDuration($faker->numberBetween(70, 200));
            $movie->setReleaseDate($faker->dateTimeBetween('-60 years', 'now'));
            $movie->setNbEntries($faker->numberBetween(5000, 10000000));
            $movie->setBudget($faker->randomFloat(2, 50000, 200000000));
            $movie->setUrl($faker->url());

            // Ajouter 1 à 3 catégories aléatoires
            $randomCategories = $faker->randomElements($categoryArray, $faker->numberBetween(1, 3));
            foreach ($randomCategories as $category) {
                $movie->addCategory($category);
            }

            // Ajouter 2 à 6 acteurs aléatoires
            $randomActors = $faker->randomElements($actorsArray, $faker->numberBetween(2, 6));
            foreach ($randomActors as $actor) {
                $movie->addActor($actor);
            }

            // Ajouter un réalisateur aléatoire
            $movie->setDirector($faker->randomElement($directorsArray));

            $manager->persist($movie);
        }

        // Sauvegarder toutes les entités
        $manager->flush();

        echo "\n✅ Fixtures chargées avec succès !\n";
        echo "- " . count($actorsArray) . " acteurs créés\n";
        echo "- " . count($directorsArray) . " réalisateurs créés\n";
        echo "- " . count($categoryArray) . " catégories créées\n";
        echo "- 199 films créés\n";
    }
}
