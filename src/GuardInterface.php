<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Passport\BadgeInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
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
     * 返回守卫名称
     *
     * @return string
     */
    public function name(): string;

    /**
     * 判断请求是否需要进行认证
     * 
     * 返回true时会调用 authenticate 方法进行认证，否则不进行认证
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function supports(ServerRequestInterface $request): bool;

    /**
     * 直接认证用户
     *
     * @param UserInterface $user
     * @param ServerRequestInterface $request
     * @param string|null $authenticator
     * @param BadgeInterface[] $badges
     * @return ResponseInterface|null
     */
    public function authenticateUser(UserInterface $user, ServerRequestInterface $request, ?string $authenticator = null, array $badges = []): ?ResponseInterface;

    /**
     * 处理认证请求; 返回null表示请求不需要认证
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function authenticate(ServerRequestInterface $request): ?ResponseInterface;

    /**
     * 处理注销请求
     *
     * @param TokenInterface|null $token
     * @return ResponseInterface|null
     */
    public function logout(?TokenInterface $token = null): ?ResponseInterface;

    /**
     * 检查令牌所属用户是否具有指定的权限
     *
     * @param TokenInterface $token
     * @param string|array $attribute
     * @param mixed $subject
     * @return boolean
     */
    public function isGranted(TokenInterface $token, string|array $attribute, mixed $subject = null): bool;
}
