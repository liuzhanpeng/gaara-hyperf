<?php

declare(strict_types=1);

namespace GaaraHyperf\TokenStorage;

use GaaraHyperf\Token\TokenInterface;

/**
 * 用户令牌存储器接口.
 *
 * 存储认证令牌，用于恢复认证状态
 */
interface TokenStorageInterface
{
    /**
     * 获取指定key的认证令牌.
     */
    public function get(string $key): ?TokenInterface;

    /**
     * 设置指定key的认证令牌.
     */
    public function set(string $key, TokenInterface $token);

    /**
     * 删除指定key的认证令牌.
     */
    public function delete(string $key): void;
}
