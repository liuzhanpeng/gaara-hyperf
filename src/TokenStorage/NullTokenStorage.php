<?php

declare(strict_types=1);

namespace GaaraHyperf\TokenStorage;

use GaaraHyperf\Token\TokenInterface;

/**
 * 用于无状态认证的TokenStorage实现.
 */
class NullTokenStorage implements TokenStorageInterface
{
    public function get(string $key): ?TokenInterface
    {
        return null;
    }

    public function set(string $key, TokenInterface $token): void
    {
    }

    public function delete(string $key): void
    {
    }
}
