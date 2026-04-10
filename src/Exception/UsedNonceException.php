<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * Nonce 已使用异常.
 */
class UsedNonceException extends InvalidCredentialsException
{
    public function __construct(
        string $message,
        private string $nonce,
        string $userIdentifier = ''
    ) {
        return parent::__construct($message, $userIdentifier);
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }
}
