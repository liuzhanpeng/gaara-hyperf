<?php

declare(strict_types=1);

namespace GaaraHyperf\PasswordHasher;

/**
 * 密码哈希器解析器接口.
 */
interface PasswordHasherResolverInterface
{
    /**
     * 通过名称解析密码哈希器.
     */
    public function resolve(string $name = 'default'): PasswordHasherInterface;
}
