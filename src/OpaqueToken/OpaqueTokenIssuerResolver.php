<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

use Hyperf\Contract\ContainerInterface;

class OpaqueTokenIssuerResolver implements OpaqueTokenIssuerResolverInterface
{
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
            throw new \InvalidArgumentException("Opaque Token发行器不存在: $name");
        }

        $opaqueTokenIssuerId = $this->opaqueTokenIssuerMap[$name];
        $opaqueTokenIssuer = $this->container->get($opaqueTokenIssuerId);
        if (!$opaqueTokenIssuer instanceof OpaqueTokenIssuerInterface) {
            throw new \LogicException(sprintf('Opaque Token发行器 "%s" 必须实现 %s 接口', $opaqueTokenIssuerId, OpaqueTokenIssuerInterface::class));
        }

        return $opaqueTokenIssuer;
    }
}
