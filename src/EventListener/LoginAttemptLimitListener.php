<?php

declare(strict_types=1);

namespace GaaraHyperf\EventListener;

use GaaraHyperf\Constants;
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\Exception\TooManyLoginAttemptsException;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\RateLimiter\RateLimiterInterface;
use GaaraHyperf\RateLimiter\SlidingWindowRateLimiter;
use Hyperf\Redis\Redis;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 登录尝试限制监听器.
 */
class LoginAttemptLimitListener implements EventSubscriberInterface
{
    private RateLimiterInterface $rateLimiter;

    public function __construct(
        Redis $redis,
        private IPResolverInterface $ipResolver,
        private string $prefix = 'default',
        private int $limit = 5,
        private int $interval = 300,
    ) {
        $this->rateLimiter = new SlidingWindowRateLimiter(
            redis: $redis,
            prefix: sprintf('%s:login_attempt_limit:%s', Constants::__PREFIX, $this->prefix),
            interval: $this->interval,
            limit: $this->limit,
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            CheckPassportEvent::class => ['checkPassport', Priority::HIGH], // 设置高优先级，确保在认证前进行限流检查
            AuthenticationSuccessEvent::class => ['onAuthenticationSuccess', Priority::NORMAL],
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        if (! $event->getAuthenticator()->isInteractive()) {
            return;
        }

        $passport = $event->getPassport();
        $request = $event->getRequest();

        $userIdentifier = $passport->getUserIdentifier();
        $ip = $this->ipResolver->resolve($request);

        $result = $this->rateLimiter->attempt($userIdentifier . $ip);
        if (! $result->isAccepted()) {
            throw new TooManyLoginAttemptsException(
                message: 'Too many login attempts. Please try again later.',
                userIdentifier: $userIdentifier,
                retryAfter: $result->getRetryAfter()
            );
        }
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        if (! $event->getAuthenticator()->isInteractive()) {
            return;
        }

        $token = $event->getToken();
        $userIdentifier = $token->getUserIdentifier();
        $ip = $this->ipResolver->resolve($event->getRequest());

        $this->rateLimiter->reset($userIdentifier . $ip);
    }
}
