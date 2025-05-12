<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

/**
 * 认证守卫解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface GuardResolverInterface
{
    /**
     * 返回所有认证守卫名称
     *
     * @return string[]
     */
    public function getGuardNames(): array;

    /**
     * 获取认证守卫
     *
     * @param string $guardName
     * @return GuardInterface
     */
    public function resolve(string $guardName): GuardInterface;
}
