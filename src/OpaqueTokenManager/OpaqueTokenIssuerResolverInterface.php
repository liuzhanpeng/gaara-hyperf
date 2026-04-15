<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

/**
 * OpaqueToken令牌颁发器解析器接口.
 */
interface OpaqueTokenIssuerResolverInterface
{
    public function resolve(string $name = 'default'): OpaqueTokenIssuerInterface;
}
