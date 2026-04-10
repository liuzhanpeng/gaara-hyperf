<?php

declare(strict_types=1);

namespace GaaraHyperf\RateLimiter;

/**
 * 限流器接口.
 */
interface RateLimiterInterface
{
    /**
     * 消耗一次登录尝试.
     */
    public function attempt(string $key): LimitResult;

    /**
     * 重置登录尝试次数.
     */
    public function reset(string $key): void;
}
