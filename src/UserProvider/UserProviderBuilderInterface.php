<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider;

/**
 * 用户提供者构建器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
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
