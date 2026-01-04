<?php

declare(strict_types=1);

namespace GaaraHyperf\EventListener;

use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\Exception\TooManyLoginAttemptsException;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\LoginRateLimiter\LoginRateLimiterFactory;
use GaaraHyperf\LoginRateLimiter\LoginRateLimiterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 登录限流监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LoginRateLimitListener implements EventSubscriberInterface
{
    private LoginRateLimiterInterface $loginRateLimiter;

    public function __construct(
        private LoginRateLimiterFactory $loginRateLimiterFactory,
        private IPResolverInterface $ipResolver,
        string $type = 'sliding_window',
        int $limit = 5,
        int $interval = 300,
        string $prefix = 'default'
    ) {
        $this->loginRateLimiter = $this->loginRateLimiterFactory->create([
            'type' => $type,
            'options' => [
                'limit' => $limit,
                'interval' => $interval,
                'prefix' => $prefix,
            ]
        ]);
    }

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
            throw new TooManyLoginAttemptsException(
                message: 'Too many login attempts. Please try again later.',
                userIdentifier: $userIdentifier,
                retryAfter: $result->getRetryAfter()
            );
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
