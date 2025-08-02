<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\LoginThrottler;

/**
 * 登录限流器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface LoginThrottlerInterface
{
    /**
     * 尝试登录
     *
     * @param string $username
     * @param string $ip
     * @return boolean
     */
    public function canAttempt(string $username, string $ip): bool;

    /**
     * 记录一次登录失败尝试
     *
     * @param string $username
     * @param string $ip
     * @return void
     */
    public function hit(string $username, string $ip): void;

    /**
     * 登录成功时清理记录
     *
     * @param string $username
     * @param string $ip
     * @return void
     */
    public function clear(string $username, string $ip): void;

    /**
     * 获取剩余可用次数
     *
     * @param string $username
     * @param string $ip
     * @return integer
     */
    public function getRemainingAttempts(string $username, string $ip): int;

    /**
     * 获取下一次可尝试的时间戳
     *
     * @param string $username
     * @param string $ip
     * @return integer
     */
    public function getRetryAfter(string $username, string $ip): int;
}
