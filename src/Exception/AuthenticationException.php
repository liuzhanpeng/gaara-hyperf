<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * 认证异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationException extends \Exception
{
    /**
     * @param string $message
     * @param string $userIdentifier
     */
    public function __construct(
        string $message,
        private string $userIdentifier = '',
    ) {
        parent::__construct($message);
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
