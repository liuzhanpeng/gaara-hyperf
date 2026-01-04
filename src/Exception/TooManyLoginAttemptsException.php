<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * 登录次数限制异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class TooManyLoginAttemptsException extends AuthenticationException
{
    public function __construct(
        string $message,
        private int $retryAfter,
        string $userIdentifier = '',
    ) {
        parent::__construct($message, $userIdentifier);
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
