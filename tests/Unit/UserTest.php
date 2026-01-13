<?php

namespace App\Tests\Unit;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserEntity()
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
        $this->assertEquals('John', $user->getFirstname());
        $this->assertEquals('Doe', $user->getLastname());
    }

    public function testUserRoles()
    {
        $user = new User();
        $this->assertContains('ROLE_USER', $user->getRoles());

        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }
}
