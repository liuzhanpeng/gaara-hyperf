<?php

declare(strict_types=1);

namespace GaaraHyperf;

use GaaraHyperf\Exception\UnauthenticatedException;
use GaaraHyperf\Token\TokenContextInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\UserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证上下文.
 */
class AuthContext
{
    private ?UserInterface $user = null;

    public function __construct(
        private ServerRequestInterface $request,
        private TokenContextInterface $tokenContext,
        private GuardResolver $guardResolver,
    ) {
    }

    /**
     * 登录.
     */
    public function login(UserInterface $user, string $guard, ?string $authenticator = null, array $badges = []): ?ResponseInterface
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
     * 登出.
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
     * 返回当前令牌.
     */
    public function getToken(): ?TokenInterface
    {
        return $this->tokenContext->getToken();
    }

    /**
     * 返回当前用户.
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
     */
    public function isAuthenticated(): bool
    {
        $token = $this->getToken();
        if ($token === null) {
            return false;
        }

        $guard = $this->guardResolver->resolve($token->getGuardName());
        if (! $guard->isTokenAuthenticated($token)) {
            return false;
        }

        return ! is_null($this->getUser());
    }

    /**
     * 是否有权限.
     */
    public function isGranted(mixed $object, mixed $action = null): bool
    {
        if (! $this->isAuthenticated()) {
            return false;
        }

        $token = $this->getToken();
        $guard = $this->guardResolver->resolve($token->getGuardName());

        return $guard->isGranted($token, $object, $action);
    }
}
