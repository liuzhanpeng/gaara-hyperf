<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Event\LoginFailureEvent;
use Lzpeng\HyperfAuthGuard\Event\LoginSuccessEvent;
use Lzpeng\HyperfAuthGuard\Exception\TooManyLoginAttemptsException;
use Lzpeng\HyperfAuthGuard\RateLimiter\CacheStorage;
use Lzpeng\HyperfAuthGuard\Utils\IpResolver;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class LoginThrottlingListener implements EventSubscriberInterface
{
    private RateLimiterFactoryInterface $rateLimiterFactory;

    public function __construct(
        private array $options,
        private IpResolver $ipResolver,
        private ContainerInterface $container,
    ) {
        $this->rateLimiterFactory = new RateLimiterFactory(
            $this->options,
            new CacheStorage($this->container->get(CacheInterface::class))
        );
    }

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
        echo '-----------------------';
        $passport = $event->getPassport();
        $request = $event->getRequest();

        $userIdentifier = $passport->getUser()->getIdentifier();
        $ip = $this->ipResolver->resolve($request);

        $userLimiter = $this->rateLimiterFactory->create($userIdentifier);
        $rateLimit = $userLimiter->consume();
        if (!$rateLimit->isAccepted()) {
            throw new TooManyLoginAttemptsException(
                sprintf('用户登录尝试过于频繁，请在%s之后再试', $rateLimit->getRetryAfter())
            );
        }

        $ipLimiter = $this->rateLimiterFactory->create($ip);
        $rateLimit = $ipLimiter->consume();
        if (!$rateLimit->isAccepted()) {
            throw new TooManyLoginAttemptsException(
                sprintf('IP地址登录尝试过于频繁，请在%s之后再试', $rateLimit->getRetryAfter())
            );
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $token = $event->getToken();
        $this->rateLimiterFactory->create($token->getUser()->getIdentifier())->reset();
        $this->rateLimiterFactory->create($this->ipResolver->resolve($event->getRequest()))->reset();
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();
        $this->rateLimiterFactory->create($exception->getUserIdentifier())->consume();
        $this->rateLimiterFactory->create($this->ipResolver->resolve($event->getRequest()))->consume();
    }
}
