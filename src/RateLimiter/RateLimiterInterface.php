<?php

declare(strict_types=1);

namespace GaaraHyperf\RateLimiter;

/**
 * 限流器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface RateLimiterInterface
{
    /**
     * 消耗一次登录尝试
     *
     * @param string $key
     * @return LimitResult
     */
    public function attempt(string $key): LimitResult;

    /**
     * 重置登录尝试次数
     *
     * @param string $key
     * @return void
     */
    public function reset(string $key): void;
}
