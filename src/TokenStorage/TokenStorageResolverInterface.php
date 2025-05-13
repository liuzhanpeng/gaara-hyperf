<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\TokenStorage;

/**
 * TokenStorage解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface TokenStorageResolverInterface
{
    /**
     * 获取指定守卫的TokenStorage
     *
     * @param string $guardName
     * @return TokenStorageInterface
     */
    public function resolve(string $guardName): TokenStorageInterface;
}
