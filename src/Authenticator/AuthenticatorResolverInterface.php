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
     * 返回指定认证守卫下所有认证器的id
     *
     * @param string $guardName 认证守卫名称
     * @return string[]
     */
    public function getAuthenticatorIds(string $guardName): array;

    /**
     * 解析认证器
     *
     * @param string $authenticatorId
     * @return AuthenticatorInterface
     */
    public function resolve(string $authenticatorId): AuthenticatorInterface;
}
