<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

class IPNotInWhiteListException extends AuthenticationException
{
    public function getDisplayMessage(): string
    {
        return 'IP不在白名单中';
    }
}
