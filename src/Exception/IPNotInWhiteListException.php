<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * 白名单外IP异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class IPNotInWhiteListException extends AuthenticationException
{
    public function __construct(
        string $message,
        private string $ip,
        string $userIdentifier = '',
    ) {
        parent::__construct($message, $userIdentifier);
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
