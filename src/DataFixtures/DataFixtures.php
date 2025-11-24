<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Movie;
use App\Entity\Director;

class DataFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(?Generator $faker)
    {
        $this->faker = $faker;
    }

    public function load(ObjectManager $manager): void
    {
        $actorsArray = $this->createActors($manager);
        $directorsArray = $this->createDirectors($manager);
        $categoryArray = $this->createCategories($manager);
        $this->createMovies($manager, $actorsArray, $directorsArray, $categoryArray);

        $manager->flush();

        echo "\n✅ Fixtures chargées avec succès !\n";
        echo "- " . count($actorsArray) . " acteurs créés\n";
        echo "- " . count($directorsArray) . " réalisateurs créés\n";
        echo "- " . count($categoryArray) . " catégories créées\n";
        echo "- 199 films créés\n";
    }

    private function createActors(ObjectManager $manager): array
    {
        $actorsArray = [];

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

        foreach ($famousActors as $actorData) {
            $actor = new Actor();
            $actor->setFirstName($actorData[0]);
            $actor->setLastName($actorData[1]);
            $actor->setBio($this->faker->paragraph(3));
            $actor->setDob($this->faker->dateTimeBetween('-80 years', '-20 years'));

            if ($this->faker->boolean(15)) {
                $dob = $actor->getDob();
                $actor->setDod($this->faker->dateTimeBetween($dob, 'now'));
            }

            $actorsArray[] = $actor;
            $manager->persist($actor);
        }

        for ($i = 0; $i < 110; $i++) {
            $actor = new Actor();
            $actor->setFirstName($this->faker->firstName());
            $actor->setLastName($this->faker->lastName());
            $actor->setBio($this->faker->paragraph(2));
            $actor->setDob($this->faker->dateTimeBetween('-70 years', '-18 years'));

            if ($this->faker->boolean(20)) {
                $dob = $actor->getDob();
                $actor->setDod($this->faker->dateTimeBetween($dob, 'now'));
            }

            $actorsArray[] = $actor;
            $manager->persist($actor);
        }

        return $actorsArray;
    }

    private function createDirectors(ObjectManager $manager): array
    {
        $directorsArray = [];

        $famousDirectors = [
            ['Christopher', 'Nolan'], ['Steven', 'Spielberg'], ['Quentin', 'Tarantino'],
            ['Martin', 'Scorsese'], ['Francis Ford', 'Coppola'], ['Ridley', 'Scott'],
            ['Denis', 'Villeneuve'], ['Wes', 'Anderson'], ['David', 'Fincher'],
            ['James', 'Cameron'], ['Peter', 'Jackson'], ['Tim', 'Burton'],
            ['Clint', 'Eastwood'], ['Spike', 'Lee'], ['Alfonso', 'Cuarón'],
            ['Guillermo', 'Del Toro'], ['Jean-Luc', 'Godard'], ['François', 'Truffaut'],
            ['Luc', 'Besson'], ['Jacques', 'Audiard']
        ];

        foreach ($famousDirectors as $directorData) {
            $director = new Director();
            $director->setFirstname($directorData[0]);
            $director->setLastname($directorData[1]);
            $director->setDob($this->faker->dateTimeBetween('-80 years', '-40 years'));

            if ($this->faker->boolean(25)) {
                $dob = $director->getDob();
                $director->setDod($this->faker->dateTimeBetween($dob, 'now'));
            }

            $directorsArray[] = $director;
            $manager->persist($director);
        }

        for ($i = 0; $i < 30; $i++) {
            $director = new Director();
            $director->setFirstname($this->faker->firstName());
            $director->setLastname($this->faker->lastName());
            $director->setDob($this->faker->dateTimeBetween('-85 years', '-30 years'));

            if ($this->faker->boolean(20)) {
                $dob = $director->getDob();
                $director->setDod($this->faker->dateTimeBetween($dob, 'now'));
            }

            $directorsArray[] = $director;
            $manager->persist($director);
        }

        return $directorsArray;
    }

    private function createCategories(ObjectManager $manager): array
    {
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

        return $categoryArray;
    }

    private function createMovies(
        ObjectManager $manager,
        array $actorsArray,
        array $directorsArray,
        array $categoryArray
    ): void {
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

        foreach ($famousMovies as $movieTitle) {
            $movie = new Movie();
            $movie->setName($movieTitle);
            $movie->setDescription($this->faker->paragraph(4));
            $movie->setDuration($this->faker->numberBetween(80, 240));
            $movie->setReleaseDate($this->faker->dateTimeBetween('-50 years', 'now'));
            $movie->setNbEntries($this->faker->numberBetween(100000, 15000000));
            $movie->setBudget($this->faker->randomFloat(2, 1000000, 250000000));

            $slug = strtolower(str_replace(' ', '-', $movieTitle));
            $movie->setUrl("https://www.imdb.com/title/{$slug}");

            $randomCategories = $this->faker->randomElements(
                $categoryArray,
                $this->faker->numberBetween(1, 3)
            );
            foreach ($randomCategories as $category) {
                $movie->addCategory($category);
            }

            $randomActors = $this->faker->randomElements(
                $actorsArray,
                $this->faker->numberBetween(3, 8)
            );
            foreach ($randomActors as $actor) {
                $movie->addActor($actor);
            }

            $movie->setDirector($this->faker->randomElement($directorsArray));

            $manager->persist($movie);
        }

        for ($i = 0; $i < 164; $i++) {
            $movie = new Movie();

            $titleFormats = [
                $this->faker->words(2, true),
                'The ' . $this->faker->word(),
                $this->faker->firstName() . ' ' . $this->faker->lastName(),
                'Le ' . $this->faker->word() . ' ' . $this->faker->word(),
                $this->faker->word() . ' ' . $this->faker->word()
            ];
            $movie->setName(ucwords($this->faker->randomElement($titleFormats)));

            $movie->setDescription($this->faker->paragraph(
                $this->faker->numberBetween(2, 5)
            ));
            $movie->setDuration($this->faker->numberBetween(70, 200));
            $movie->setReleaseDate($this->faker->dateTimeBetween('-60 years', 'now'));
            $movie->setNbEntries($this->faker->numberBetween(5000, 10000000));
            $movie->setBudget($this->faker->randomFloat(2, 50000, 200000000));
            $movie->setUrl($this->faker->url());

            $randomCategories = $this->faker->randomElements(
                $categoryArray,
                $this->faker->numberBetween(1, 3)
            );
            foreach ($randomCategories as $category) {
                $movie->addCategory($category);
            }

            $randomActors = $this->faker->randomElements(
                $actorsArray,
                $this->faker->numberBetween(2, 6)
            );
            foreach ($randomActors as $actor) {
                $movie->addActor($actor);
            }

            $movie->setDirector($this->faker->randomElement($directorsArray));

            $manager->persist($movie);
        }
    }
}
