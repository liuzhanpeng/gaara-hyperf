<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Factory;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;

/**
 * 认证器工厂接口
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticatorFactoryInterface
{
    /**
     * 创建认证器
     *
     * @param array $options
     * @param string $userProviderId
     * @param string $eventDispatcherId
     * @return AuthenticatorInterface
     */
    public function create(array $options, string $userProviderId, string $eventDispatcherId): AuthenticatorInterface;
}
