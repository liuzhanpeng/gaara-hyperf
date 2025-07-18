<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * 认证器构建器接口
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticatorBuilderInterface
{
    /**
     * 创建认证器
     *
     * @param array $options
     * @param UserProviderInterface $userProvider
     * @param EventDispatcher $eventDispatcher
     * @return AuthenticatorInterface
     */
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface;
}
