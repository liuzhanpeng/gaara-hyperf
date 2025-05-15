<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Passport\BadgeInterface;
use Lzpeng\HyperfAuthGuard\User\UserInterface;
use Psr\Http\Message\ResponseInterface;

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
     * @param RequestInterface $request
     * @param BadgeInterface[] $badges
     * @return ResponseInterface|null
     */
    public function authenticateUser(UserInterface $user, AuthenticatorInterface $authenticator, RequestInterface $request, array $badges = []): ?ResponseInterface;

    /**
     * 处理认证请求
     *
     * @param RequestInterface $request
     * @return ResponseInterface|null
     */
    public function authenticate(RequestInterface $request): ?ResponseInterface;
}
