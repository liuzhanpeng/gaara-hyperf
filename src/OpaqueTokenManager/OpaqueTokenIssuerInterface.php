<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\Token\TokenInterface;

/**
 * opaque token令牌颁发器接口.
 */
interface OpaqueTokenIssuerInterface
{
    /**
     * 发布一个opaque token.
     */
    public function issue(TokenInterface $token): OpaqueToken;

    /**
     * 解析并返回一个opaque token.
     */
    public function resolve(string $accessToken): ?OpaqueToken;

    /**
     * 撤销一个opaque token.
     */
    public function revoke(string $accessToken): void;
}
