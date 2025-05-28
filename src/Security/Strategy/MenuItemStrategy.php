<?php

namespace App\Security\Strategy;

use App\Security\PermissionStrategyInterface;
use App\Entity\User;

class MenuItemStrategy implements PermissionStrategyInterface {

    public function supports(string $action, object $subject) : bool {
        dump($action);
        dump($subject);
        return true;
    }

    public function canPerform(User $user, object $subject) : bool {
        dump($user);
        dump($subject);
        return true;
    }
}
