<?php

declare(strict_types=1);

namespace GaaraHyperf;

use GaaraHyperf\Passport\BadgeInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证守卫接口.
 */
interface GuardInterface
{
    /**
     * 返回守卫名称.
     */
    public function name(): string;

    /**
     * 返回用户提供器.
     */
    public function getUserProvider(): UserProviderInterface;

    /**
     * 判断请求是否需要进行认证
     *
     * 返回true时会调用 authenticate 方法进行认证，否则不进行认证
     */
    public function supports(ServerRequestInterface $request): bool;

    /**
     * 直接认证用户.
     *
     * @param BadgeInterface[] $badges
     */
    public function authenticateUser(UserInterface $user, ServerRequestInterface $request, ?string $authenticator = null, array $badges = []): ?ResponseInterface;

    /**
     * 处理认证请求; 返回null表示请求不需要认证
     */
    public function authenticate(ServerRequestInterface $request): ?ResponseInterface;

    /**
     * 处理注销请求
     */
    public function logout(?TokenInterface $token = null): ?ResponseInterface;

    /**
     * 判断令牌是否已通过当前守卫的信任判定.
     */
    public function isTokenAuthenticated(?TokenInterface $token): bool;

    /**
     * 检查令牌所属用户是否具有指定的权限.
     */
    public function isGranted(TokenInterface $token, mixed $object, mixed $action = null): bool;
}
