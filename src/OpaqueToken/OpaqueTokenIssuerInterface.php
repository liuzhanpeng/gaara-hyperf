<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    /**
     * 从请求中提取access token字符串 
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function extractAccessToken(ServerRequestInterface $request): ?string;
}
