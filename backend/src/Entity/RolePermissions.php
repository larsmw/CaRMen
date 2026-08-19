<?php

namespace App\Entity;

use App\Repository\RolePermissionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RolePermissionsRepository::class)]
class RolePermissions
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private string $role;

    #[ORM\Column(type: 'json')]
    private array $permissions = [];

    public function __construct(string $role, array $permissions = [])
    {
        $this->role = $role;
        $this->permissions = $permissions;
    }

    public function getRole(): string { return $this->role; }

    public function getPermissions(): array { return $this->permissions; }
    public function setPermissions(array $permissions): static { $this->permissions = $permissions; return $this; }
}
