<?php

namespace CaRMen\Security\Strategy;

use CaRMen\Security\PermissionStrategyInterface;
use CaRMen\Entity\User;

class MenuItemStrategy implements PermissionStrategyInterface {

    public function supports(string $action, object $subject) : bool {
        return true;
    }

    public function canPerform(User $user, object $subject) : bool {
        if ($subject->getRoute() == '/login') {
            return false;
        }
        return true;
    }
}
