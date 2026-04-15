<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * OpaqueToken管理器接口.
 */
interface OpaqueTokenManagerInterface
{
    /**
     * 发布一个opaque token.
     */
    public function issue(TokenInterface $token): ResponseInterface;

    /**
     * 解析并返回opaque token.
     */
    public function resolve(ServerRequestInterface $request): ?OpaqueToken;

    /**
     * 撤销access token.
     */
    public function revoke(ServerRequestInterface $request): void;
}
