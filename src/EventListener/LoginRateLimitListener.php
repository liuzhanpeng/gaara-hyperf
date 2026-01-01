<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\AuthenticationFailureEvent;
use Lzpeng\HyperfAuthGuard\Event\AuthenticationSuccessEvent;
use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\TooManyLoginAttemptsException;
use Lzpeng\HyperfAuthGuard\IPResolver\IPResolverInterface;
use Lzpeng\HyperfAuthGuard\LoginRateLimiter\LoginRateLimiterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 登录限流监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LoginRateLimitListener implements EventSubscriberInterface
{
    public function __construct(
        private LoginRateLimiterInterface $loginRateLimiter,
        private IPResolverInterface $ipResolver,
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            CheckPassportEvent::class => 'checkPassport',
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        if (!$event->getAuthenticator()->isInteractive()) {
            return;
        }

        $passport = $event->getPassport();
        $request = $event->getRequest();

        $userIdentifier = $passport->getUserIdentifier();
        $ip = $this->ipResolver->resolve($request);

        $result = $this->loginRateLimiter->check($userIdentifier . $ip);
        if (!$result->isAccepted() || $result->getRemaining() === 0) {
            throw new TooManyLoginAttemptsException($userIdentifier, $result->getRetryAfter());
        }
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        if (!$event->getAuthenticator()->isInteractive()) {
            return;
        }

        $token = $event->getToken();
        $this->loginRateLimiter->reset($token->getUserIdentifier() . $this->ipResolver->resolve($event->getRequest()));
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        if (!$event->getAuthenticator()->isInteractive()) {
            return;
        }

        $exception = $event->getException();
        $this->loginRateLimiter->attempt($exception->getUserIdentifier() . $this->ipResolver->resolve($event->getRequest()));
    }
}
