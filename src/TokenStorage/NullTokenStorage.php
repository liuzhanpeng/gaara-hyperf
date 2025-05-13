<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\TokenStorage;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 用于无状态认证的TokenStorage实现
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class NullTokenStorage implements TokenStorageInterface
{
    /**
     * @inheritDoc
     */
    public function get(string $key): ?TokenInterface
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, TokenInterface $token): void {}

    /**
     * @inheritDoc
     */
    public function delete(string $key): void {}
}
