<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use InvalidArgumentException;

/**
 * OpaqueToken令牌颁发器解析器.
 */
class OpaqueTokenIssuerResolver implements OpaqueTokenIssuerResolverInterface
{
    private array $opaqueTokenIssuers = [];

    public function __construct(
        private array $factories,
    ) {
    }

    public function resolve(string $name = 'default'): OpaqueTokenIssuerInterface
    {
        if (! isset($this->opaqueTokenIssuers[$name])) {
            if (! isset($this->factories[$name])) {
                throw new InvalidArgumentException("Opaque Token Issuer does not exist: {$name}");
            }

            $this->opaqueTokenIssuers[$name] = ($this->factories[$name])();
        }

        return $this->opaqueTokenIssuers[$name];
    }
}
