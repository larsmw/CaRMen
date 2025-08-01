<?php

namespace CaRMen\Security;

use CaRMen\Entity\User;

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
        dump($strategies);
        $this->strategies = $strategies;
    }

    public function isGranted(string $action, User $user, object $subject): bool
    {
        dump($this->strategies);
        foreach ($this->strategies as $strategy) {
            dump($strategy);
            if ($strategy->supports($action, $subject)) {
                return $strategy->canPerform($user, $subject);
            }
        }

        throw new \LogicException(sprintf('No strategy found for action "%s" on subject "%s".', $action, get_class($subject)));
    }
}
