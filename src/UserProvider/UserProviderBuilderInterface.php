<?php

declare(strict_types=1);

namespace GaaraHyperf\UserProvider;

/**
 * 用户提供者构建器接口.
 */
interface UserProviderBuilderInterface
{
    /**
     * 创建用户提供者实例.
     *
     * @param array $options 配置选项
     */
    public function create(array $options): UserProviderInterface;
}
