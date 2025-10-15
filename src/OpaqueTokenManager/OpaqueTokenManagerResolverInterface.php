<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueTokenManager;

/**
 * OpaqueToken管理器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface OpaqueTokenManagerResolverInterface
{
    /**
     * @param string $name
     * @return OpaqueTokenManagerInterface
     */
    public function resolve(string $name = 'default'): OpaqueTokenManagerInterface;
}
