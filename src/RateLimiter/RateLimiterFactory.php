<?php

declare(strict_types=1);

namespace GaaraHyperf\RateLimiter;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Redis\Redis;
use GaaraHyperf\Constants;

/**
 * 限流器创建工厂
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class RateLimiterFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(array $config): RateLimiterInterface
    {
        $type = $config['type'] ?? 'sliding_window';
        $options = $config['options'] ?? [];

        switch ($type) {
            case 'sliding_window':
                return new SlidingWindowRateLimiter(
                    redis: $this->container->get(Redis::class),
                    interval: $options['interval'] ?? 300,
                    limit: $options['limit'] ?? 5,
                    prefix: sprintf('%s:login_rate_limiter:sliding_window:%s', Constants::__PREFIX, $options['prefix'] ?? 'default'),
                );
            case 'token_bucket':
                return new TokenBucketRateLimiter(
                    redis: $this->container->get(Redis::class),
                    limit: $options['limit'] ?? 10,
                    rate: $options['rate'] ?? 1.0,
                    prefix: sprintf('%s:login_rate_limiter:token_bucket:%s', Constants::__PREFIX, $options['prefix'] ?? 'default'),
                );
            case 'fixed_window':
                return new FixedWindowRateLimiter(
                    redis: $this->container->get(Redis::class),
                    interval: $options['interval'] ?? 60,
                    limit: $options['limit'] ?? 10,
                    prefix: sprintf('%s:login_rate_limiter:fixed_window:%s', Constants::__PREFIX, $options['prefix'] ?? 'default'),
                );
            default:
                throw new \InvalidArgumentException("Unsupported rate limiter type: {$type}");
        }
    }
}
