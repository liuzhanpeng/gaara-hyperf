<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfToken;

/**
 * CSRF令牌管理器解析器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface CsrfTokenManagerResolverInterface
{
    /**
     * @param string $name
     * @return CsrfTokenManagerInterface
     */
    public function resolve(string $name = 'default'): CsrfTokenManagerInterface;
}
