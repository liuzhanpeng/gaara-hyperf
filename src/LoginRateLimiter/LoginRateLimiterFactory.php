<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\LoginRateLimiter;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Redis\Redis;
use Lzpeng\HyperfAuthGuard\Config\LoginRateLimiterConfig;
use Lzpeng\HyperfAuthGuard\Constants;

/**
 * 登录限流器创建工厂
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LoginRateLimiterFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(LoginRateLimiterConfig $loginRateLimiterConfig): LoginRateLimiterInterface
    {
        $type = $loginRateLimiterConfig->type();
        $options = $loginRateLimiterConfig->options();

        switch ($type) {
            case 'no_limit':
                return new NoLoginRateLimiter();
            case 'sliding_window':
                return new SlidingWindowLoginRateLimiter(
                    $this->container->get(Redis::class),
                    $options['interval'] ?? 300,
                    $options['max_attempts'] ?? 5,
                    Constants::LOGIN_RATE_LIMITER_PREFIX
                );
            default:
                throw new \InvalidArgumentException("Unsupported rate limiter type: {$type}");
        }
    }
}
