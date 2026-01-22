<?php

namespace App\Tests\Unit;

use App\Entity\Actor;
use PHPUnit\Framework\TestCase;

class ActorTest extends TestCase
{
    public function testActorEntity()
    {
        $actor = new Actor();
        $actor->setFirstname('Leonardo');
        $actor->setLastname('DiCaprio');

        $this->assertEquals('Leonardo', $actor->getFirstname());
        $this->assertEquals('DiCaprio', $actor->getLastname());
        $this->assertEquals('DiCaprio Leonardo', $actor->getFullName());
    }

    public function testActorAge()
    {
        $actor = new Actor();
        $dob = new \DateTime('1974-11-11');
        $actor->setDob($dob);

        // Calcul de l'âge attendu
        $now = new \DateTime();
        $expectedAge = $now->diff($dob)->y;

        $this->assertEquals($expectedAge, $actor->getAge());
    }
}
