<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider\Builder;

use Lzpeng\HyperfAuthGuard\UserProvider\ModelUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderBuilderInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;

/**
 * 基于Hyperf内置数据库模型用户提供者构建器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ModelUserProviderBuilder implements UserProviderBuilderInterface
{
    /**
     * @inheritDoc
     */
    public function create(array $options): UserProviderInterface
    {
        if (!isset($options['class'])) {
            throw new \InvalidArgumentException("The 'class' option must be configured for the model user provider.");
        }

        if (!isset($options['identifier'])) {
            throw new \InvalidArgumentException("The 'identifier' option must be configured for the model user provider.");
        }

        return new ModelUserProvider($options['class'], $options['identifier']);
    }
}
