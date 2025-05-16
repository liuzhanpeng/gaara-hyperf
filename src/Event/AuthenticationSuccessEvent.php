<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/***
 * 认证成功事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationSuccessEvent
{
    /**
     * @param TokenInterface $token
     */
    public function __construct(
        private TokenInterface $token,
    ) {}

    /**
     * 返回认证令牌
     * 
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }
}
