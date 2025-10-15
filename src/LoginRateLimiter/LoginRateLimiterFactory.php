<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\LoginRateLimiter;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Redis\Redis;
use Lzpeng\HyperfAuthGuard\Config\ComponentConfig;
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

    public function create(ComponentConfig $config): LoginRateLimiterInterface
    {
        $type = $config->type();
        $options = $config->options();

        switch ($type) {
            case 'no_limit':
                return new NoLoginRateLimiter();
            case 'sliding_window':
                return new SlidingWindowLoginRateLimiter(
                    redis: $this->container->get(Redis::class),
                    interval: $options['interval'] ?? 300,
                    limit: $options['limit'] ?? 5,
                    prefix: sprintf('%s:login_rate_limiter:sliding_window:%s', Constants::__PREFIX, $options['prefix'] ?? 'default'),
                );
            default:
                throw new \InvalidArgumentException("Unsupported rate limiter type: {$type}");
        }
    }
}
