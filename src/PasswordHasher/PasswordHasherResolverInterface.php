<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\PasswordHasher;

/**
 * 密码哈希器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface PasswordHasherResolverInterface
{
    /**
     * 通过名称解析密码哈希器
     *
     * @param string $name
     * @return PasswordHasherInterface
     */
    public function resolve(string $name = 'default'): PasswordHasherInterface;
}
