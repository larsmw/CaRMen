<?php

namespace App\Repository;

use App\Entity\RolePermissions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RolePermissionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RolePermissions::class);
    }

    /** @return array<string, string[]> */
    public function getMap(): array
    {
        $map = [];
        foreach ($this->findAll() as $rp) {
            $map[$rp->getRole()] = $rp->getPermissions();
        }
        return $map;
    }
}
