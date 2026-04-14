<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * 签名过期异常.
 */
class SignatureExpiredException extends InvalidSignatureException
{
    public function __construct(
        string $message,
        private int $timestamp,
        private int $currentTime,
        string $userIdentifier = ''
    ) {
        parent::__construct($message, $userIdentifier);
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
