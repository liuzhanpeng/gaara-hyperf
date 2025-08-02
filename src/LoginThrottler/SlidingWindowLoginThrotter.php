<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\LoginThrottler;

use Hyperf\Redis\Redis;

/**
 * 基于滑动窗口算法的登录限流器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class SlidingWindowLoginThrotter implements LoginThrottlerInterface
{
    public function __construct(
        private Redis $redis,
        private array $options
    ) {
        $this->options = array_merge([
            'max_attempts' => 5,
            'interval' => 300,
        ], $this->options);
    }

    public function canAttempt(string $username, string $ip): bool
    {
        $now = microtime(true);
        $min = $now - $this->options['interval'];
        $attempts = $this->redis->zCount($this->getKey($username, $ip), "{$min}", "{$now}");

        return $attempts < $this->options['max_attempts'];
    }

    public function hit(string $username, string $ip): void
    {
        $now = microtime(true);
        $min = $now - $this->options['interval'];
        $key = $this->getKey($username, $ip);

        // 先清理过期记录
        $this->redis->zRemRangeByScore($key, '0', "{$min}");

        // 添加当前尝试记录
        $this->redis->zAdd($key, $now, $now);

        // 设置key过期时间，防止内存泄漏
        $this->redis->expire($key, (int)($this->options['interval'] + 60));
    }

    public function clear(string $username, string $ip): void
    {
        $this->redis->del($this->getKey($username, $ip));
    }

    public function getRemainingAttempts(string $username, string $ip): int
    {
        $now = microtime(true);
        $min = $now - $this->options['interval'];
        $attempts = $this->redis->zCount($this->getKey($username, $ip), "{$min}", "{$now}");

        return max(0, $this->options['max_attempts'] - $attempts);
    }

    public function getRetryAfter(string $username, string $ip): int
    {
        $now = microtime(true);
        $min = $now - $this->options['interval'];
        $key = $this->getKey($username, $ip);

        $attempts = $this->redis->zCount($key, "{$min}", "{$now}");
        if ($attempts < $this->options['max_attempts']) {
            return 0;
        }

        // 获取最早的尝试时间（用于计算何时解除限制）
        $firstAttemptTime = $this->redis->zRange($key, 0, 0, ['WITHSCORES' => true]);
        if (empty($firstAttemptTime)) {
            return 0;
        }

        // zRange返回的是 [member => score] 的关联数组
        $firstScore = array_values($firstAttemptTime)[0];
        $retryAfter = $firstScore + $this->options['interval'] - $now;

        return max(0, (int) $retryAfter);
    }

    private function getKey(...$key): string
    {
        return "login_throttler:" . implode('.', $key);
    }
}
