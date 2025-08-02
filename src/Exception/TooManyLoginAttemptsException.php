<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

class TooManyLoginAttemptsException extends AuthenticationException
{
    public function getDisplayMessage(): string
    {
        return $this->message;
    }
}
