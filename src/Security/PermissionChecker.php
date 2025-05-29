<?php

namespace App\Security;

use App\Entity\User;

class PermissionChecker {
/**
     * @var PermissionStrategyInterface[]
     */
    private iterable $strategies;

    /**
     * @param $strategies
     */
    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    public function isGranted(string $action, User $user, object $subject): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($action, $subject)) {
                return $strategy->canPerform($user, $subject);
            }
        }

        throw new \LogicException(sprintf('No strategy found for action "%s" on subject "%s".', $action, get_class($subject)));
    }
}
