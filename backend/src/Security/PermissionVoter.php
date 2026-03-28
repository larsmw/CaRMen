<?php

namespace App\Security;

use App\Repository\RolePermissionsRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class PermissionVoter extends Voter
{
    public const PERMISSIONS = [
        'CONTACT_CREATE',
        'CONTACT_EDIT',
        'CONTACT_DELETE',
        'ACCOUNT_CREATE',
        'ACCOUNT_EDIT',
        'ACCOUNT_DELETE',
        'DEAL_CREATE',
        'DEAL_EDIT',
        'DEAL_DELETE',
        'ACTIVITY_DELETE',
    ];

    /** @var array<string, string[]>|null */
    private ?array $cache = null;

    public function __construct(
        private readonly AuthorizationCheckerInterface $auth,
        private readonly RoleHierarchyInterface $roleHierarchy,
        private readonly RolePermissionsRepository $repo,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::PERMISSIONS, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // Admins always have all permissions
        if ($this->auth->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $rolePermsMap = $this->loadMap();

        // Expand user's roles via hierarchy (e.g. ROLE_MANAGER → [ROLE_MANAGER, ROLE_SALES, ROLE_USER])
        $userRoles = array_map(fn($r) => (string) $r, $token->getRoleNames());
        $effectiveRoles = $this->roleHierarchy->getReachableRoleNames($userRoles);

        foreach ($effectiveRoles as $role) {
            if (isset($rolePermsMap[$role]) && in_array($attribute, $rolePermsMap[$role], true)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string[]> */
    private function loadMap(): array
    {
        return $this->cache ??= $this->repo->getMap();
    }
}
