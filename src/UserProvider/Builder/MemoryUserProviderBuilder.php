<?php

declare(strict_types=1);

namespace GaaraHyperf\UserProvider\Builder;

use GaaraHyperf\UserProvider\MemoryUserProvider;
use GaaraHyperf\UserProvider\UserProviderBuilderInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;

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
