<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\LoginRateLimiter;

/**
 * 无限制登录限流器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class NoLoginRateLimiter implements LoginRateLimiterInterface
{
    public function __construct() {}

    public function attempt(string $key): LimitResult
    {
        return new LimitResult(true, PHP_INT_MAX, 0);
    }

    public function check(string $key): LimitResult
    {
        return new LimitResult(true, PHP_INT_MAX, 0);
    }

    public function reset(string $key): void {}
}
