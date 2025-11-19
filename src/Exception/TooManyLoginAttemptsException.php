<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

/**
 * 重新限制异常
 */
class TooManyLoginAttemptsException extends AuthenticationException
{
    public function __construct(
        string $userIdentifier = '',
        private int $retryAfter
    ) {
        parent::__construct($userIdentifier);
    }

    /**
     * 返回多少秒后可重试
     *
     * @return integer
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
