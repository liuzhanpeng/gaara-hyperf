<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * 签名过期异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class SignatureExpiredException extends InvalidCredentialsException
{
    public function __construct(
        string $message,
        private int $timestamp,
        private int $currentTime,
        string $userIdentifier = ''
    ) {
        return parent::__construct($message, $userIdentifier);
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getCurrentTime(): int
    {
        return $this->currentTime;
    }
}
