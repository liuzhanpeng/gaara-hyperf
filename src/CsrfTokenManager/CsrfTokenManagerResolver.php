<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

use InvalidArgumentException;

/**
 * 内置的CSRF令牌管理器解析器.
 */
class CsrfTokenManagerResolver implements CsrfTokenManagerResolverInterface
{
    private array $csrfTokenManagers = [];

    public function __construct(
        private array $factories,
    ) {
    }

    public function resolve(string $name = 'default'): CsrfTokenManagerInterface
    {
        if (! isset($this->csrfTokenManagers[$name])) {
            if (! isset($this->factories[$name])) {
                throw new InvalidArgumentException("CSRF Token Manager does not exist: {$name}");
            }

            $this->csrfTokenManagers[$name] = ($this->factories[$name])();
        }

        return $this->csrfTokenManagers[$name];
    }
}
