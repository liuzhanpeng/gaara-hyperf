<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

/**
 * 白名单外IP异常
 */
class IPNotInWhiteListException extends AuthenticationException
{
    public function __construct(
        string $userIdentifier = '',
        private string $ip
    ) {
        parent::__construct($userIdentifier);
    }

    /**
     * 返回用户id
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }
}
