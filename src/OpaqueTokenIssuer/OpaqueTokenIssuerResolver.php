<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueTokenIssuer;

use Hyperf\Contract\ContainerInterface;

/**
 * OpaqueToken发行器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenIssuerResolver implements OpaqueTokenIssuerResolverInterface
{
    /**
     * @param array $opaqueTokenIssuerMap
     * @param ContainerInterface $container
     */
    public function __construct(
        private array $opaqueTokenIssuerMap,
        private ContainerInterface $container,
    ) {}

    /**
     * @inheritDoc
     */
    public function resolve(string $name = 'default'): OpaqueTokenIssuerInterface
    {
        if (!isset($this->opaqueTokenIssuerMap[$name])) {
            throw new \InvalidArgumentException("Opaque Token Issuer does not exist: $name");
        }

        $opaqueTokenIssuerId = $this->opaqueTokenIssuerMap[$name];
        $opaqueTokenIssuer = $this->container->get($opaqueTokenIssuerId);
        if (!$opaqueTokenIssuer instanceof OpaqueTokenIssuerInterface) {
            throw new \LogicException(sprintf('Opaque Token Issuer "%s" must implement %s interface', $opaqueTokenIssuerId, OpaqueTokenIssuerInterface::class));
        }

        return $opaqueTokenIssuer;
    }
}
