<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RquestMatcher;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 请求匹配器接口
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface RequestMatcherInterface
{
    /**
     * 是否匹配
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function matches(ServerRequestInterface $request): bool;
}
