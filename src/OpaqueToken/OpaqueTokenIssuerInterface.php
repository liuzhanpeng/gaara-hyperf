<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * opaque token发行器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface OpaqueTokenIssuerInterface
{
    /**
     * 发布一个opaque token
     *
     * @param TokenInterface $token
     * @return OpaqueToken
     */
    public function issue(TokenInterface $token): OpaqueToken;

    /**
     * 撤销一个opaque token
     *
     * @param string $tokenStr
     * @return void
     */
    public function revoke(string $tokenStr): void;

    /**
     * 解析一个opaque token 返回一个用户令牌
     *
     * @param string $tokenStr
     * @param boolean $refresh
     * @return TokenInterface|null
     */
    public function resolve(string $tokenStr, bool $refresh = true): ?TokenInterface;
}
