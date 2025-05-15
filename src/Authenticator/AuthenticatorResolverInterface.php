<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

/**
 * 认证器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticatorResolverInterface
{
    /**
     * 返回所有认证器的id
     *
     * @return string[]
     */
    public function getAuthenticatorIds(): array;

    /**
     * 解析认证器
     *
     * @param string $authenticatorId
     * @return AuthenticatorInterface
     */
    public function resolve(string $authenticatorId): AuthenticatorInterface;
}
