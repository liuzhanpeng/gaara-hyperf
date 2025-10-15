<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueTokenManager;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * opaque token管理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface OpaqueTokenManagerInterface
{
    /**
     * 发布一个opaque token
     *
     * @param TokenInterface $token
     * @return string
     */
    public function issue(TokenInterface $token): string;

    /**
     * 解析一个opaque token 返回一个用户令牌
     *
     * @param string $tokenStr
     * @return TokenInterface|null
     */
    public function resolve(string $tokenStr): ?TokenInterface;

    /**
     * 撤销一个opaque token
     *
     * @param string $tokenStr
     * @return void
     */
    public function revoke(string $tokenStr): void;
}
