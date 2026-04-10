<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;

/**
 * 内置的CSRF令牌管理器解析器.
 */
class CsrfTokenManagerResolver implements CsrfTokenManagerResolverInterface
{
    public function __construct(
        private array $csrfTokenManagerMap,
        private ContainerInterface $container,
    ) {
    }

    public function resolve(string $name = 'default'): CsrfTokenManagerInterface
    {
        if (! isset($this->csrfTokenManagerMap[$name])) {
            throw new InvalidArgumentException("CSRF Token Manager does not exist: {$name}");
        }

        $csrfTokenManagerId = $this->csrfTokenManagerMap[$name];
        $csrfTokenManager = $this->container->get($csrfTokenManagerId);
        if (! $csrfTokenManager instanceof CsrfTokenManagerInterface) {
            throw new LogicException(sprintf('CSRF Token Manager "%s" must implement %s interface', $csrfTokenManagerId, CsrfTokenManagerInterface::class));
        }

        return $csrfTokenManager;
    }
}
