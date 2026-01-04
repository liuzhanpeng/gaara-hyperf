<?php

declare(strict_types=1);

namespace GaaraHyperf;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Exception\UnauthenticatedException;
use GaaraHyperf\Token\TokenContextInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\UserInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证上下文
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthContext
{
    private ?UserInterface $user = null;

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
            throw new UnauthenticatedException();
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
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getToken();
        if (is_null($token)) {
            return null;
        }

        $userIdentifier = $token->getUserIdentifier();
        $guard = $this->guardResolver->resolve($token->getGuardName());
        $this->user = $guard->getUserProvider()->findByIdentifier($userIdentifier);

        return $this->user;
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
