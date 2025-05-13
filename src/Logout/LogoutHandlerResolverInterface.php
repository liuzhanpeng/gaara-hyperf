<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Logout;

/**
 * 登出处理器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface LogoutHandlerResolverInterface
{
    /**
     * 通过认证守卫名称获取登出处理器
     *
     * @param string $guardName
     * @return LogoutHandlerInterface
     */
    public function resolve(string $guardName): LogoutHandlerInterface;
}
