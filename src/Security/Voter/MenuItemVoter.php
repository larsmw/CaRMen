<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security;;

final class MenuItemVoter extends Voter
{
    public const EDIT = 'MENU_EDIT';
    public const VIEW = 'MENU_VIEW';
    
    protected function supports(string $attribute, mixed $subject): bool
    {
        dump(__METHOD__);
        dump($attribute);
        dump($subject);
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        //        return true;
        dump(__METHOD__);
        dump($attribute);
        dump($subject);
        dump($token);
        dump($user);
        //        die();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                // logic to determine if the user can EDIT
                // return true or false
                break;

            case self::VIEW:
                // logic to determine if the user can VIEW
                return true;
                break;
        }

        return false;
    }
}
