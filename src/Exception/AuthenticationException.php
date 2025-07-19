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
        int $code = 0,
        ?\Throwable $previous = null,
        string $userIdentifier = ''
    ) {
        parent::__construct($message, $code, $previous);
        $this->userIdentifier = $userIdentifier;
    }

    public static function from(
        string $message,
        string $userIdentifier = '',
        ?\Throwable $previous = null
    ): static {
        return new static($message, 0, $previous, $userIdentifier);
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
        return $this->message ?: '认证异常';
    }
}
