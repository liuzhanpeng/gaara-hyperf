<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\User\UserInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证上下文
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthContext
{
    /**
     * @param ServerRequestInterface $request
     * @param TokenContextInterface $tokenContext
     * @param GuardResolver $guardResolver
     */
    public function __construct(
        private ServerRequestInterface $request,
        private TokenContextInterface $tokenContext,
        private GuardResolver $guardResolver,
    ) {}

    /**
     * 登录
     *
     * @param UserInterface $user
     * @param string $guard
     * @param string|null $authenticator
     * @param array $badges
     * @return ResponseInterface
     */
    public function login(UserInterface $user, string $guard, ?string $authenticator = null, array $badges = []): ResponseInterface
    {
        $guard = $this->guardResolver->resolve($guard);

        return $guard->authenticateUser(
            $user,
            $this->request,
            $authenticator,
            $badges
        );
    }

    /**
     * 登出
     *
     * @return ResponseInterface
     */
    public function logout(): ResponseInterface
    {
        if (! $this->isAuthenticated()) {
            throw new AuthenticationException($this->getToken()->getUserIdentifier());
        }

        $guard = $this->guardResolver->resolve($this->getToken()->getGuardName());

        return $guard->logout($this->getToken());
    }

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

        $token = $this->getToken();
        $guard = $this->guardResolver->resolve($token->getGuardName());

        return $guard->isGranted($token, $attribute, $subject);
    }
}
