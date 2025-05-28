<?php

namespace App\Security;

use App\Entity\User;

interface PermissionStrategyInterface {

    public function supports(string $action, object $subject): bool;

    public function canPerform(User $user, object $subject): bool;

}
