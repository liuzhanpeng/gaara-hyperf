<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;

/**
 * OpaqueToken管理器解析器.
 */
class OpaqueTokenManagerResolver implements OpaqueTokenManagerResolverInterface
{
    public function __construct(
        private array $opaqueTokenManagerMap,
        private ContainerInterface $container,
    ) {
    }

    public function resolve(string $name = 'default'): OpaqueTokenManagerInterface
    {
        if (! isset($this->opaqueTokenManagerMap[$name])) {
            throw new InvalidArgumentException("Opaque Token Manager does not exist: {$name}");
        }

        $opaqueTokenManagerId = $this->opaqueTokenManagerMap[$name];
        $opaqueTokenManager = $this->container->get($opaqueTokenManagerId);
        if (! $opaqueTokenManager instanceof OpaqueTokenManagerInterface) {
            throw new LogicException(sprintf('Opaque Token Manager "%s" must implement %s interface', $opaqueTokenManagerId, OpaqueTokenManagerInterface::class));
        }

        return $opaqueTokenManager;
    }
}
