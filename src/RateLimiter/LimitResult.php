<?php

declare(strict_types=1);

namespace GaaraHyperf\RateLimiter;

/**
 * 限流结果.
 */
class LimitResult
{
    /**
     * @param bool $accepted 是否通过限流检查
     * @param int $remaining 剩余可用数
     * @param int $retryAfter 多少秒后可以重试
     */
    public function __construct(
        private bool $accepted,
        private int $remaining,
        private int $retryAfter,
    ) {
    }

    /**
     * 是否通过限流检查.
     */
    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    /**
     * 剩余可用数.
     */
    public function getRemaining(): int
    {
        return $this->remaining;
    }

    /**
     * 返回可重试时间(多少秒后可以重试).
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
