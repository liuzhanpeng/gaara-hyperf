<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Passport\BadgeInterface;
use Lzpeng\HyperfAuthGuard\User\UserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证守卫接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface GuardInterface
{
    /**
     * 直接认证用户
     *
     * @param UserInterface $user
     * @param AuthenticatorInterface $authenticator
     * @param ServerRequestInterface $request
     * @param BadgeInterface[] $badges
     * @return ResponseInterface|null
     */
    public function authenticateUser(UserInterface $user, AuthenticatorInterface $authenticator, ServerRequestInterface $request, array $badges = []): ?ResponseInterface;

    /**
     * 处理认证请求
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function authenticate(ServerRequestInterface $request): ?ResponseInterface;
}
