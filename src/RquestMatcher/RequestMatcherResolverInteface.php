<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RquestMatcher;

/**
 * 请求匹配器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface RequestMatcherResolverInteface
{
    /**
     * 通过认证守卫ID获取对应的请求匹配器
     *
     * @param string $guardName
     * @return RequestMatcherInterface
     */
    public function resolve(string $guardName): RequestMatcherInterface;
}
