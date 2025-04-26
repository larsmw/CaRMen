<?php

namespace App\Repository;

use App\Entity\MenuItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuItem>
 */
class MenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class);
    }

    /**
     * @param $value : Name of the menu for which to finde menu items.
     * @return MenuItem[] Returns an array of MenuItem objects
     */
    public function findByMenuName($value): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.menu = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @param $value : Name of the menu for which to finde menu items.
     * @return MenuItem Returns a MenuItem object
     */
    public function findOneByMenu($value): ?MenuItem
    {
      return $this->createQueryBuilder('m')
          ->andWhere('m.menu = :val')
          ->setParameter('val', $value)
          ->getQuery()
          ->getOneOrNullResult()
          ;
    }
}
