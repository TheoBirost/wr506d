<?php

namespace App\Tests\Unit;

use App\Entity\Category;
use App\Entity\Movie;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryEntity()
    {
        $category = new Category();
        $category->setName('Action');

        $this->assertEquals('Action', $category->getName());
    }

    public function testAddRemoveMovie()
    {
        $category = new Category();
        $movie = new Movie();
        $movie->setName('Die Hard');

        $category->addMovie($movie);
        $this->assertCount(1, $category->getMovies());
        $this->assertEquals(1, $category->getMoviesCount());

        $category->removeMovie($movie);
        $this->assertCount(0, $category->getMovies());
        $this->assertEquals(0, $category->getMoviesCount());
    }
}
