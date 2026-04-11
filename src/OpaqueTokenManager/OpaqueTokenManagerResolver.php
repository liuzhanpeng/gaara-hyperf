<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use InvalidArgumentException;

/**
 * OpaqueToken管理器解析器.
 */
class OpaqueTokenManagerResolver implements OpaqueTokenManagerResolverInterface
{
    private array $opaqueTokenManagers = [];

    public function __construct(
        private array $factories,
    ) {
    }

    public function resolve(string $name = 'default'): OpaqueTokenManagerInterface
    {
        if (! isset($this->opaqueTokenManagers[$name])) {
            if (! isset($this->factories[$name])) {
                throw new InvalidArgumentException("Opaque Token Manager does not exist: {$name}");
            }

            $this->opaqueTokenManagers[$name] = ($this->factories[$name])();
        }

        return $this->opaqueTokenManagers[$name];
    }
}
