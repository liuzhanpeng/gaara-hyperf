<?php

declare(strict_types=1);

namespace GaaraHyperf\PasswordHasher;

/**
 * 密码哈希器接口.
 */
interface PasswordHasherInterface
{
    /**
     * 密码加密.
     */
    public function hash(string $password): string;

    /**
     * 密码验证
     */
    public function verify(string $password, string $hashedPassword): bool;
}
