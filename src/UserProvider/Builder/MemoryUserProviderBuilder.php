<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider\Builder;

use Lzpeng\HyperfAuthGuard\UserProvider\MemoryUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderBuilderInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;

/**
 * 基于内存用户提供者构建器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class MemoryUserProviderBuilder implements UserProviderBuilderInterface
{
    /**
     * @inheritDoc
     */
    public function create(array $options): UserProviderInterface
    {
        if (!isset($options['users'])) {
            throw new \InvalidArgumentException('The "users" option must be configured for the memory user provider');
        }

        return new MemoryUserProvider($options['users']);
    }
}
