<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider\Factory;

use Lzpeng\HyperfAuthGuard\UserProvider\ModelUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;

class ModelUserProviderFactory implements UserProviderFactoryInterface
{
    public function create(array $options): UserProviderInterface
    {
        if (!isset($options['class'])) {
            throw new \InvalidArgumentException("model类型的用户提供器必须配置class选项");
        }

        if (!isset($options['identifier'])) {
            throw new \InvalidArgumentException("model类型的用户提供器必须配置identifier选项");
        }

        return new ModelUserProvider($options['class'], $options['identifier']);
    }
}
