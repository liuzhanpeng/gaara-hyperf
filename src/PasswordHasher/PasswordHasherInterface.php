<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\PasswordHasher;

/**
 * 密码哈希器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface PasswordHasherInterface
{
    /**
     * 密码加密
     *
     * @param string $password
     * @return string
     */
    public function hash(string $password): string;

    /**
     * 密码验证
     *
     * @param string $password
     * @param string $hashedPassword
     * @return bool
     */
    public function verify(string $password, string $hashedPassword): bool;
}
