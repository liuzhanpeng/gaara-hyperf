<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

interface OpaqueTokenProcessorResolverInterface
{
    public function resolve(string $name = 'default'): OpaqueTokenProcessorInterface;
}
