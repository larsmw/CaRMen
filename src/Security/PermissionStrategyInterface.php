<?php

namespace CaRMen\Security;

use CaRMen\Entity\User;

interface PermissionStrategyInterface {

    public function supports(string $action, object $subject): bool;

    public function canPerform(User $user, object $subject): bool;

}
