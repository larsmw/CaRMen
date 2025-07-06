<?php

namespace CaRMen\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security;
use CaRMen\Entity\User;
use CaRMen\Entity\MenuItem;

final class MenuItemVoter extends Voter
{
    public const EDIT        = 'MENU_EDIT';
    public const VIEW        = 'VIEW';
    public const CREATE      = 'CREATE';
    public const MENU_VIEW   = 'MENU_VIEW';
    public const MENU_CREATE = 'MENU_CREATE';
    public const MENU_DELETE = 'MENU_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        if (in_array($attribute, [self::EDIT, self::VIEW, self::CREATE])) {
            return true;
        }
        // unhandled attribute
        dump($attribute);
        return FALSE;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        dump($attribute);

        // if the user is anonymous, do not grant access
        
        if (!$user instanceof User &&
            $subject instanceof MenuItem) {
            if (in_array($subject->getRoute(), ['/logout','/customer', '/menu/item'])) {
              return false;
            }
        } elseif ($user instanceof User) {
            if ($subject instanceof MenuItem &&
              $subject->getRoute() == '/login') {
              return false;
            }

        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::CREATE:
                if (is_object($user))
                  return true;
                break;
            case self::EDIT:
                // logic to determine if the user can EDIT
                // return true or false
                if (is_object($user))
                  return true;
                break;

            case self::MENU_DELETE:
                // logic to determine if the user can DELETE
                // return true or false
                if (is_object($user))
                  return true;
                break;

            case self::VIEW:
                // logic to determine if the user can VIEW
                //if ($user instanceof User) return true;
                return true;

        }

        return false;
    }
}
