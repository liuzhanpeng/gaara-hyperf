<?php

declare(strict_types=1);

namespace GaaraHyperf\PasswordHasher;

/**
 * 默认的密码哈希器.
 */
class DefaultPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private string $algo = PASSWORD_BCRYPT,
    ) {
    }

    public function hash(string $password): string
    {
        return password_hash($password, $this->algo);
    }

    public function verify(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }
}
