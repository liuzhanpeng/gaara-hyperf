<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

/**
 * 认证异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationException extends \Exception
{
    /**
     * @param string $userIdentifier 用户标识
     */
    public function __construct(
        private string $userIdentifier = '',
        string $message = ''
    ) {
        parent::__construct($message);
        $this->userIdentifier = $userIdentifier;
    }

    /**
     * 返回用户标识
     *
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }
}
