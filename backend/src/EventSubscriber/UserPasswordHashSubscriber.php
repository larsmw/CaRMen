<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class UserPasswordHashSubscriber
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->hashPassword($args->getObject());
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->hashPassword($args->getObject());
    }

    private function hashPassword(object $entity): void
    {
        if (!$entity instanceof User || $entity->getPlainPassword() === null) {
            return;
        }

        $entity->setPassword($this->hasher->hashPassword($entity, $entity->getPlainPassword()));
        $entity->eraseCredentials();
    }
}
