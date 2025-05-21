<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerResolverInterface;
use Lzpeng\HyperfAuthGuard\Exception\AccessDeniedException;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolverInterface;
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
    public function __construct(
        private RequestInterface $request,
        private TokenContextInterface $tokenContext,
        private LogoutHandlerResolverInterface $logoutHandlerResolver,
        private AuthorizationCheckerResolverInterface $authorizationCheckerResolver,
    ) {}

    /**
     * 登出
     *
     * @return ResponseInterface
     */
    public function logout(): ResponseInterface
    {
        if (! $this->isAuthenticated()) {
            throw new AccessDeniedException('未登录或会话已过期');
        }

        $logoutHandler = $this->logoutHandlerResolver->resolve($this->getToken()->getGuardName());
        return $logoutHandler->handle($this->request);
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

        $authorizationChecker = $this->authorizationCheckerResolver->resolve($token->getGuardName());

        return $authorizationChecker->check($token, $attribute, $subject);
    }
}
