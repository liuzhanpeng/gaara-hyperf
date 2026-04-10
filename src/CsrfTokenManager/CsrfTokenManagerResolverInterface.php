<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

/**
 * CSRF令牌管理器解析器接口.
 */
interface CsrfTokenManagerResolverInterface
{
    public function resolve(string $name = 'default'): CsrfTokenManagerInterface;
}
