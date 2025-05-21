<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider\Factory;

use Lzpeng\HyperfAuthGuard\UserProvider\MemoryUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;

class MemoryUserProviderFactory implements UserProviderFactoryInterface
{
    public function create(array $options): UserProviderInterface
    {
        if (!isset($options['users'])) {
            throw new \InvalidArgumentException("memory类型的用户提供器必须配置users选项");
        }

        return new MemoryUserProvider($options['users']);
    }
}
