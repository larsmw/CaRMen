<?php

namespace CaRMen\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use CaRMen\Entity\User;
use CaRMen\Security\PermissionChecker;

final class PermissionVoter extends Voter
{
    public const EDIT = 'POST_EDIT';
    public const VIEW = 'POST_VIEW';
    public const CUSTOMER_LIST = 'CUSTOMER_LIST';
    public const CUSTOMER_VIEW = 'CUSTOMER_VIEW';
    public const CUSTOMER_ADD  = 'CUSTOMER_ADD';
    public const CUSTOMER_EDIT = 'CUSTOMER_EDIT';

    public function __construct(private PermissionChecker $checker) {}
    
    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        if (in_array($attribute, [self::EDIT,
                                  self::VIEW,
                                  self::CUSTOMER_LIST,
                                  self::CUSTOMER_VIEW,
                                  self::CUSTOMER_ADD,
                                  self::CUSTOMER_EDIT
        ])) {
            return TRUE;
        }

        if (!is_object($subject)) {
            return false;
        }
        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        if (is_object($subject)) {
          try {
              return $this->checker->isGranted($attribute, $user, $subject);
          } catch (\LogicException) {
              return false;
          }
        }

        return true;
    }
}
