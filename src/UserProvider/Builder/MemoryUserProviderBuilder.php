<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider\Builder;

use Lzpeng\HyperfAuthGuard\UserProvider\MemoryUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderBuilderInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;

class MemoryUserProviderBuilder implements UserProviderBuilderInterface
{
    public function create(array $options): UserProviderInterface
    {
        if (!isset($options['users'])) {
            throw new \InvalidArgumentException("memory类型的用户提供器必须配置users选项");
        }

        return new MemoryUserProvider($options['users']);
    }
}
