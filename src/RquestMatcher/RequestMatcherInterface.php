<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RquestMatcher;

use Hyperf\HttpServer\Contract\RequestInterface;

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
     * @param RequestInterface $request
     * @return boolean
     */
    public function matches(RequestInterface $request): bool;
}
