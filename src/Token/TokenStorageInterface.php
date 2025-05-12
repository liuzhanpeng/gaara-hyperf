<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Token;

/**
 * 用户令牌存储器接口
 * 
 * 存储认证令牌，用于恢复认证状态
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface TokenStorageInterface
{
    /**
     * 获取指定key的认证令牌
     *
     * @param string $key
     * @return TokenInterface|null
     */
    public function get(string $key): ?TokenInterface;

    /**
     * 设置指定key的认证令牌
     *
     * @param string $key
     * @param TokenInterface $token
     * @return void
     */
    public function set(string $key, TokenInterface $token);

    /**
     * 删除指定key的认证令牌
     *
     * @param string $key
     * @return void
     */
    public function delete(string $key): void;
}
