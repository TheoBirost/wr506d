<?php

namespace App\DataFixtures;


use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use DateTimeImmutable;
use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Movie;
use http\Url;

class DataFixtures extends Fixture
{
    public function load(ObjectManager $manager) : void
    {

        $fakerActors = \Faker\Factory::create();
        $fakerActors->addProvider(new \Xylis\FakerCinema\Provider\Person($fakerActors));

        $actors = $fakerActors->actors($gender = null, $count = 140, $duplicates = false);
        foreach ($actors as $item) {

            $actor = new Actor();
            $names = explode(' ', $item);
            $actor->setFirstName($names[0]);
            $actor->setLastName($names[1]);

            $actor->setBio($fakerActors->realText($maxNbChars = 100, $indexSize = 2));
            $actorsArray[] = $actor;

            $actor->setDob($fakerActors->dateTimeThisCentury());

            if ($fakerActors->boolean(45)) {
                $dob = $actor->getDob();
                $actor->setDod($fakerActors->dateTimeBetween($dob, 'now'));
            }
            $manager->persist($actor);

        }

        $fakerMovie = \Faker\Factory::create();
        $fakerMovie->addProvider(new \Xylis\FakerCinema\Provider\Movie($fakerMovie));


        $categoryArray = [];
        $actorArray = [];

        $movies = $fakerMovie->movies(199);
        foreach ($movies as $item) {
            $movie = new Movie();

            echo $item. ' ---- '.$fakerMovie->movieGenre."\n";

            $categoryName = $fakerMovie->movieGenre;
            if (!array_key_exists($categoryName, $categoryArray)) {
                $category = new Category();
                $category->setName($categoryName);
                $manager->persist($category);

                $categoryArray[$categoryName] = $category;
            } else {
                $category = $categoryArray[$categoryName] ;
            }

            $durationMin = 60 * 60;
            $durationMax = 270 * 60;


            $movie->setName($item);

            $movie->setDescription($fakerMovie->realText($maxNbChars = 100, $indexSize = 2));
            $movie->setDuration($fakerMovie->numberBetween($durationMin , $durationMax ));
            $movie->setReleaseDate($fakerMovie->dateTimeThisCentury());
            $movie->setImage("https://placehold.co/300x400");
            $actor->setPhoto("https://placehold.co/200x400");


            $movie->addCategory($category);

            shuffle($actorsArray);
            foreach (array_slice($actorsArray, 0, rand(2 , 6))as $actorObject){
                $movie->addActor($actorObject);
            }

            $manager->persist($movie);


        }

        $manager->flush();
    }


}
