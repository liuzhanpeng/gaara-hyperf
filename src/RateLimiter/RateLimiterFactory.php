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
                $options = array_replace_recursive([
                    'limit' => 5,
                    'interval' => 300,
                    'prefix' => 'default',
                ], $options);

                return new SlidingWindowRateLimiter(
                    redis: $this->container->get(Redis::class),
                    interval: $options['interval'],
                    limit: $options['limit'],
                    prefix: sprintf('%s:login_rate_limiter:sliding_window:%s', Constants::__PREFIX, $options['prefix'] ?? 'default'),
                );
            case 'token_bucket':
                $options = array_replace_recursive([
                    'limit' => 10,
                    'rate' => 1.0,
                    'prefix' => 'default',
                ], $options);
                return new TokenBucketRateLimiter(
                    redis: $this->container->get(Redis::class),
                    limit: $options['limit'] ?? 10,
                    rate: $options['rate'] ?? 1.0,
                    prefix: sprintf('%s:login_rate_limiter:token_bucket:%s', Constants::__PREFIX, $options['prefix'] ?? 'default'),
                );
            case 'fixed_window':
                $options = array_replace_recursive([
                    'limit' => 5,
                    'interval' => 300,
                    'prefix' => 'default',
                ], $options);

                return new FixedWindowRateLimiter(
                    redis: $this->container->get(Redis::class),
                    interval: $options['interval'],
                    limit: $options['limit'],
                    prefix: sprintf('%s:login_rate_limiter:fixed_window:%s', Constants::__PREFIX, $options['prefix'] ?? 'default'),
                );
            default:
                throw new \InvalidArgumentException("Unsupported rate limiter type: {$type}");
        }
    }
}
