<?php

namespace App\Repository;

use App\Entity\Deal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    public function getPipelineSummary(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.stage, COUNT(d.id) as count, SUM(d.value) as total_value')
            ->groupBy('d.stage')
            ->getQuery()
            ->getArrayResult();
    }
}
