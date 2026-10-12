<?php

namespace App\EventSubscriber;

use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Public demo: everyone shares one login, so nobody may change or delete that
 * account (password, email, role, active flag) or use password reset — otherwise
 * one visitor could lock everyone else out until the nightly reset.
 * Off unless DEMO_ACCOUNT_EMAIL is set.
 */
class DemoModeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserRepository $users,
        #[Autowire(env: 'DEMO_ACCOUNT_EMAIL')] private string $demoEmail,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onRequest'];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($this->demoEmail === '' || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        if (\in_array($path, ['/api/forgot-password', '/api/reset-password'], true)) {
            $event->setResponse($this->deny('Password reset is disabled in the demo.'));
            return;
        }

        if (\in_array($request->getMethod(), ['PATCH', 'PUT', 'DELETE'], true)
            && preg_match('#^/api/users/([0-9a-fA-F-]{36})$#', $path, $m)
            && $this->users->find($m[1])?->getEmail() === $this->demoEmail
        ) {
            $event->setResponse($this->deny('The shared demo account cannot be changed.'));
        }
    }

    private function deny(string $message): JsonResponse
    {
        return new JsonResponse(['error' => $message, 'detail' => $message], 403);
    }
}
