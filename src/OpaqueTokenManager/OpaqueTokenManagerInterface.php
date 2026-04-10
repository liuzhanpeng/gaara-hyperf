<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\Token\TokenInterface;

/**
 * opaque token管理器接口.
 */
interface OpaqueTokenManagerInterface
{
    /**
     * 发布一个opaque token.
     */
    public function issue(TokenInterface $token): string;

    /**
     * 解析一个opaque token 返回一个用户令牌.
     */
    public function resolve(string $accessToken): ?TokenInterface;

    /**
     * 撤销一个opaque token.
     */
    public function revoke(string $accessToken): void;
}
