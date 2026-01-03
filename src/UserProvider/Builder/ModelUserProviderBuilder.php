<?php

declare(strict_types=1);

namespace GaaraHyperf\UserProvider\Builder;

use GaaraHyperf\UserProvider\ModelUserProvider;
use GaaraHyperf\UserProvider\UserProviderBuilderInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;

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
