<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

/**
 * 认证异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationException extends \RuntimeException
{
    /**
     * 用户标识
     *
     * @var string
     */
    protected string $userIdentifier;

    public function __construct(
        string $message = '',
        string $userIdentifier = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
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

    /**
     * 返回显示消息
     *
     * @return string
     */
    public function getDisplayMessage(): string
    {
        return $this->message;
    }
}
