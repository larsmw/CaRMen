<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;

final class PermissionVoter extends Voter
{
    public const EDIT = 'POST_EDIT';
    public const VIEW = 'POST_VIEW';
    public const CUSTOMER_LIST = 'CUSTOMER_LIST';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        dump($attribute);
        //return in_array($attribute, [self::EDIT, self::VIEW]);
        return is_object($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        dump($attribute);
        dump($subject);
        dump($token);
        dump($user);
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        /*        try {
            //return $this->isGranted($attribute, $user, $subject);
        } catch (\LogicException) {
            return false;
            }*/
        
        /*
        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                // logic to determine if the user can EDIT
                // return true or false
                break;

            case self::VIEW:
                // logic to determine if the user can VIEW
                // return true or false
                break;
        }

        return false;*/
        return true;
    }
}
