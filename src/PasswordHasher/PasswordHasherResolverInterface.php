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
     * 获取密码哈希器
     *
     * @param string $guardName
     * @return PasswordHasherInterface
     */
    public function resolve(string $guardName): PasswordHasherInterface;
}
