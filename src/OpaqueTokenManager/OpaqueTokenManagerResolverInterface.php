<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

/**
 * OpaqueToken管理器解析器接口.
 */
interface OpaqueTokenManagerResolverInterface
{
    public function resolve(string $name = 'default'): OpaqueTokenManagerInterface;
}
