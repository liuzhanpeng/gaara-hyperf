<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\User\UserInterface;

/**
 * 认证上下文
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthContext
{
    /**
     * @param TokenContextInterface $tokenContext
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        private TokenContextInterface $tokenContext,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    /**
     * 返回当前令牌
     *
     * @return TokenInterface|null
     */
    public function getToken(): ?TokenInterface
    {
        return $this->tokenContext->getToken();
    }

    /**
     * 返回当前用户
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->getToken()?->getUser();
    }

    /**
     * 是否已认证
     *
     * @return boolean
     */
    public function isAuthenticated(): bool
    {
        return !is_null($this->getUser());
    }

    /**
     * 是否有权限
     *
     * @param string|string[] $attribute
     * @param mixed $subject
     * @return boolean
     */
    public function isGranted(string|array $attribute, mixed $subject = null): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        return $this->authorizationChecker->check($this->getToken(), $attribute, $subject);
    }
}
