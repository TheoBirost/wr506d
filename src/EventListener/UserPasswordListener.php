<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

class UserPasswordListener
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    // Ici, on reçoit l'entité User directement
    public function prePersist(User $user): void
    {
        $plainPassword = $user->getPlainPassword();
        if ($plainPassword !== null) {
            $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
            $user->setPlainPassword(null);
        }
    }
}
