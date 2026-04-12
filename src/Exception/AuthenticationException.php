<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

use Exception;
use Throwable;

/**
 * 认证异常.
 */
class AuthenticationException extends Exception
{
    public function __construct(
        string $message,
        private string $userIdentifier = '',
        ?Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            previous: $previous
        );
    }

    /**
     * 返回用户标识.
     */
    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }
}
