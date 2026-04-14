<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * OpaqueToken编排器接口.
 */
interface OpaqueTokenProcessorInterface
{
    /**
     * 从请求中提取access token.
     */
    public function extract(ServerRequestInterface $request): ?string;

    /**
     * 发布一个opaque token.
     */
    public function issue(TokenInterface $token): OpaqueToken;

    /**
     * 解析并返回opaque token.
     */
    public function resolve(string $accessToken): ?OpaqueToken;

    /**
     * 撤销access token.
     */
    public function revoke(string $accessToken): void;

    /**
     * 根据opaque token生成响应.
     */
    public function respond(OpaqueToken $opaqueToken): ResponseInterface;
}
