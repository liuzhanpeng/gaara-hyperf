<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

/**
 * 授权检查器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthorizationCheckerResolverInterface
{
    /**
     * 创建授权检查器
     *
     * @param string $id
     * @return AuthorizationCheckerInterface
     */
    public function resolve(string $guardName): AuthorizationCheckerInterface;
}
