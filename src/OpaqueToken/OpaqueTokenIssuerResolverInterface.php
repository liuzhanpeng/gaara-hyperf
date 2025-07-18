<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

/**
 * OpaqueToken发行器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface OpaqueTokenIssuerResolverInterface
{
    /**
     * @param string $name
     * @return OpaqueTokenIssuerInterface
     */
    public function resolve(string $name = 'default'): OpaqueTokenIssuerInterface;
}
