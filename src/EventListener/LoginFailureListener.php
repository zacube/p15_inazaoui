<?php

namespace App\EventListener;

use App\Security\UserBlockedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginFailureListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof UserBlockedException || $exception->getPrevious() instanceof UserBlockedException) {
            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate('user_blocked')
            ));
        }
    }
}