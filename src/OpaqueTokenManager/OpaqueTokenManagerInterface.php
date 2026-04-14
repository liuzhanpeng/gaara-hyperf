<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderInterface;
use GaaraHyperf\Token\TokenInterface;

/**
 * opaque token管理器接口.
 */
interface OpaqueTokenManagerInterface
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

    /**
     * 返回opaque token提取器.
     */
    public function getExtractor(): AccessTokenExtractorInterface;

    /**
     * 返回opaque token响应器.
     */
    public function getResponder(): OpaqueTokenResponderInterface;
}
