<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 未认证异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UnauthenticatedException extends \RuntimeException
{

    /**
     * 用户令牌
     *
     * @var TokenInterface|null
     */
    private ?TokenInterface $token = null;

    /**
     * @param TokenInterface|null $token
     * @return self
     */
    public static function from(?TokenInterface $token): self
    {
        $self = new self('Unauthorized', 401);
        $self->token = $token;

        return $self;
    }
}
