<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

/**
 * 用户不存在异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UserNotFoundException extends AuthenticationException
{
    /**
     * 用户标识
     *
     * @var string
     */
    private string $userIdentifier;

    /**
     * @param string $userIdentifier
     * @return self
     */
    public static function fromUserIdentifier(string $userIdentifier): self
    {
        $self = new self();
        $self->userIdentifier = $userIdentifier;

        return $self;
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
