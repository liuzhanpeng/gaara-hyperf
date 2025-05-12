<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

use Lzpeng\HyperfAuthGuard\User\UserInterface;

/**
 * 认证异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationException extends \RuntimeException
{
    /**
     * 用户
     *
     * @var UserInterface|null
     */
    private ?UserInterface $user = null;

    /**
     *
     * @param string $message
     * @param UserInterface|null $user
     * @return static
     */
    public static function from(string $message, ?UserInterface $user = null): static
    {
        $self = new static($message);
        $self->user = $user;

        return $self;
    }

    /**
     * 返回用户
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }
}
