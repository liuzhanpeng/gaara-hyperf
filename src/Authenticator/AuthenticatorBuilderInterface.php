<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
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
