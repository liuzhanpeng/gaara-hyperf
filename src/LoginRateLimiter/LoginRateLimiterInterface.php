<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\LoginRateLimiter;

/**
 * 登录限流器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface LoginRateLimiterInterface
{
    /**
     * 消耗一次登录尝试
     *
     * @param string $key
     * @return LimitResult
     */
    public function attempt(string $key): LimitResult;

    /**
     * 获取当前限流状态
     *
     * @param string $key
     * @return LimitResult
     */
    public function check(string $key): LimitResult;

    /**
     * 重置登录尝试次数
     *
     * @param string $key
     * @return void
     */
    public function reset(string $key): void;
}
