<?php

namespace CaRMen\Repository;

use CaRMen\Entity\MenuItem;
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

    /** @return MenuItem[] */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.menu', 'ASC')
            ->addOrderBy('m.weight', 'ASC')
            ->getQuery()
            ->getResult()
        ;
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
            ->orderBy('m.weight', 'ASC')
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
