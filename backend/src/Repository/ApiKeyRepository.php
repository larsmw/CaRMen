<?php

namespace App\Repository;

use App\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ApiKey> */
class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    /** The active key matching a presented plain key, or null. */
    public function findActiveByPlainKey(string $plainKey): ?ApiKey
    {
        $prefix = ApiKey::prefixOf($plainKey);
        if ($prefix === null) {
            return null;
        }

        $key = $this->findOneBy(['prefix' => $prefix]);

        return $key !== null && $key->isActive() && $key->matches($plainKey) ? $key : null;
    }
}
