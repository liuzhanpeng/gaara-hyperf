<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/***
 * 认证成功事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationSuccessEvent implements EventInterface
{
    /**
     * @param string $guardName
     * @param TokenInterface $token
     */
    public function __construct(
        private string $guardName,
        private TokenInterface $token,
    ) {}

    /**
     * @inheritDoc
     */
    public function getGuardName(): string
    {
        return $this->guardName;
    }

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
