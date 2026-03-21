<?php

namespace CaRMen\Security\Voter;

use CaRMen\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PermissionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_contains($attribute, '.');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->getId() === 1) {
            return true;
        }

        [$subjectName, $action] = explode('.', $attribute, 2);

        foreach ($user->getRoleEntities() as $role) {
            foreach ($role->getPermissions() as $permission) {
                if ($permission->getSubject() === $subjectName
                    && $permission->getAction() === $action) {
                    return true;
                }
            }
        }

        return false;
    }
}
