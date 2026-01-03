<?php

declare(strict_types=1);

namespace GaaraHyperf\RequestMatcher;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 请求匹配器接口
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface RequestMatcherInterface
{
    /**
     * 是否匹配请求
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function matchesPattern(ServerRequestInterface $request): bool;

    /**
     * 是否匹配到注销请求
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function matchesLogout(ServerRequestInterface $request): bool;

    /**
     * 是否匹配到排除的请求（不需要认证的请求）
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function matchesExcluded(ServerRequestInterface $request): bool;
}
