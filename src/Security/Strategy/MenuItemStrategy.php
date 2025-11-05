<?php

namespace CaRMen\Security\Strategy;

use CaRMen\Security\PermissionStrategyInterface;
use CaRMen\Entity\User;

class MenuItemStrategy implements PermissionStrategyInterface {

    public function supports(string $action, object $subject) : bool {
        dump($subject);
        dump($action);
        return true;
    }

    public function canPerform(User $user, object $subject) : bool {
        dump($subject);
        dump($user);
        dump($subject->getRoute());
        if ($subject->getRoute() == '/login') {
            return false;
        }
        return true;
    }
}
