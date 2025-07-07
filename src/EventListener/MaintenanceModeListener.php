<?php

namespace CaRMen\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'kernel.request', priority: 4096)]
class MaintenanceModeListener
{
    public function __construct(private readonly bool $maintenanceMode) {}

    /**
     * Checks if Maintenance mode is set. Redirects accordingly.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->maintenanceMode) {
            if ($_SERVER['REQUEST_URI'] !== '/maintenance')
              $event->setResponse(new RedirectResponse('/maintenance'));
        } else {
            if ($_SERVER['REQUEST_URI'] == '/maintenance')
              $event->setResponse(new RedirectResponse('/'));
        }
    }
}
