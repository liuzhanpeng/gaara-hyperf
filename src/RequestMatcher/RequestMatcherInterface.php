<?php

declare(strict_types=1);

namespace GaaraHyperf\RequestMatcher;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 请求匹配器接口.
 */
interface RequestMatcherInterface
{
    /**
     * 是否匹配请求
     */
    public function matchesPattern(ServerRequestInterface $request): bool;

    /**
     * 是否匹配到注销请求
     */
    public function matchesLogout(ServerRequestInterface $request): bool;

    /**
     * 是否匹配到排除的请求（不需要认证的请求）.
     */
    public function matchesExcluded(ServerRequestInterface $request): bool;
}
