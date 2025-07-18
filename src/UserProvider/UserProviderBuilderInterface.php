<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider;

interface UserProviderBuilderInterface
{
    /**
     * 创建用户提供者实例
     *
     * @param array $options 配置选项
     * @return UserProviderInterface
     */
    public function create(array $options): UserProviderInterface;
}
