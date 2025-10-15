<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\AccessTokenExtractor;

/**
 * 访问令牌提取器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AccessTokenExtractorResolverInterface
{
    /**
     * 解析访问令牌提取器
     *
     * @param string $name
     * @return AccessTokenExtractorInterface
     */
    public function resolve(string $name = 'header'): AccessTokenExtractorInterface;
}
