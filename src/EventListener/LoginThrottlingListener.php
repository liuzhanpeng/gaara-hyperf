<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Event\LoginFailureEvent;
use Lzpeng\HyperfAuthGuard\Event\LoginSuccessEvent;
use Lzpeng\HyperfAuthGuard\Exception\TooManyLoginAttemptsException;
use Lzpeng\HyperfAuthGuard\LoginThrottler\LoginThrottlerInterface;
use Lzpeng\HyperfAuthGuard\Utils\IpResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginThrottlingListener implements EventSubscriberInterface
{
    public function __construct(
        private LoginThrottlerInterface $loginThrottler,
        private IpResolver $ipResolver,
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            CheckPassportEvent::class => 'checkPassport',
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $request = $event->getRequest();

        $userIdentifier = $passport->getUserIdentifier();
        $ip = $this->ipResolver->resolve($request);

        if (!$this->loginThrottler->canAttempt($userIdentifier, $ip)) {
            throw new TooManyLoginAttemptsException(
                sprintf('用户登录尝试过于频繁，请在%s之后再试', $this->loginThrottler->getRetryAfter($userIdentifier, $ip))
            );
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $token = $event->getToken();
        $this->loginThrottler->clear($token->getUser()->getIdentifier(), $this->ipResolver->resolve($event->getRequest()));
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();
        $this->loginThrottler->hit($exception->getUserIdentifier(), $this->ipResolver->resolve($event->getRequest()));
    }
}
