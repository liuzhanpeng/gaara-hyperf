<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\PasswordHasher;

/**
 * 内置的密码哈希器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordHasher implements PasswordHasherInterface
{
    /**
     * @param string $algo
     */
    public function __construct(
        private string $algo = PASSWORD_BCRYPT,
    ) {}

    /**
     * @inheritDoc
     */
    public function hash(string $password): string
    {
        return password_hash($password, $this->algo);
    }

    /**
     * @inheritDoc
     */
    public function verify(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }
}
