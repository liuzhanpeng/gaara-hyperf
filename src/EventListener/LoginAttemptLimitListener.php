<?php

declare(strict_types=1);

namespace GaaraHyperf\EventListener;

use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\Exception\TooManyLoginAttemptsException;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\RateLimiter\RateLimiterFactory;
use GaaraHyperf\RateLimiter\RateLimiterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 登录尝试限制监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LoginAttemptLimitListener implements EventSubscriberInterface
{
    private RateLimiterInterface $rateLimiter;

    public function __construct(
        private RateLimiterFactory $rateLimiterFactory,
        private IPResolverInterface $ipResolver,
        string $type = 'sliding_window',
        int $limit = 5,
        int $interval = 300,
        string $prefix = 'default'
    ) {
        $this->rateLimiter = $this->rateLimiterFactory->create([
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

        $result = $this->rateLimiter->attempt($userIdentifier . $ip);
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
        $userIdentifier = $token->getUserIdentifier();
        $ip = $this->ipResolver->resolve($event->getRequest());

        $this->rateLimiter->reset($userIdentifier . $ip);
    }
}
