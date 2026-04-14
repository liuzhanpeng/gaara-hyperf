<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use InvalidArgumentException;

class OpaqueTokenProcessorResolver implements OpaqueTokenProcessorResolverInterface
{
    private array $opaqueTokenProcessors = [];

    public function __construct(
        private array $factories,
    ) {
    }

    public function resolve(string $name = 'default'): OpaqueTokenProcessorInterface
    {
        if (! isset($this->opaqueTokenProcessors[$name])) {
            if (! isset($this->factories[$name])) {
                throw new InvalidArgumentException("Opaque Token Processor does not exist: {$name}");
            }

            $this->opaqueTokenProcessors[$name] = ($this->factories[$name])();
        }

        return $this->opaqueTokenProcessors[$name];
    }
}
