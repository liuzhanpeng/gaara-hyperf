<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

use Exception;
use GaaraHyperf\Token\TokenInterface;

/**
 * 未认证异常.
 */
class UnauthenticatedException extends Exception
{
    public function __construct(
        string $message = 'Unauthenticated',
        private ?TokenInterface $token = null
    ) {
        parent::__construct($message);
    }

    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }
}
