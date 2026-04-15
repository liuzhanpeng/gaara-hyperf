<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

interface OpaqueTokenManagerResolverInterface
{
    public function resolve(string $name = 'default'): OpaqueTokenManagerInterface;
}
