<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider\Factory;

use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;

interface UserProviderFactoryInterface
{
    /**
     * 创建用户提供者
     *
     * @return UserProviderInterface
     */
    public function create(array $options): UserProviderInterface;
}
