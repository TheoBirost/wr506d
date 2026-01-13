<?php

namespace App\Tests\Unit;

use App\Entity\Movie;
use App\Entity\Category;
use App\Entity\Actor;
use PHPUnit\Framework\TestCase;

class MovieTest extends TestCase
{
    public function testMovieEntity()
    {
        $movie = new Movie();
        $movie->setName('Inception');
        $movie->setDescription('A mind-bending thriller');
        $movie->setDuration(148);

        $this->assertEquals('Inception', $movie->getName());
        $this->assertEquals('A mind-bending thriller', $movie->getDescription());
        $this->assertEquals(148, $movie->getDuration());
        $this->assertEquals('2h 28min', $movie->getFormattedDuration());
    }

    public function testAddRemoveCategory()
    {
        $movie = new Movie();
        $category = new Category();
        $category->setName('Sci-Fi');

        $movie->addCategory($category);
        $this->assertCount(1, $movie->getCategories());
        $this->assertTrue($movie->getCategories()->contains($category));

        $movie->removeCategory($category);
        $this->assertCount(0, $movie->getCategories());
        $this->assertFalse($movie->getCategories()->contains($category));
    }

    public function testAddRemoveActor()
    {
        $movie = new Movie();
        $actor = new Actor();
        $actor->setLastname('DiCaprio');

        $movie->addActor($actor);
        $this->assertCount(1, $movie->getActors());
        $this->assertEquals(1, $movie->getActorCount());

        $movie->removeActor($actor);
        $this->assertCount(0, $movie->getActors());
        $this->assertEquals(0, $movie->getActorCount());
    }
}
