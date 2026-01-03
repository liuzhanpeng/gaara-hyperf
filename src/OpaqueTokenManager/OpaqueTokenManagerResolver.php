<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use Psr\Container\ContainerInterface;

/**
 * OpaqueToken管理器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenManagerResolver implements OpaqueTokenManagerResolverInterface
{
    /**
     * @param array $opaqueTokenManagerMap
     * @param ContainerInterface $container
     */
    public function __construct(
        private array $opaqueTokenManagerMap,
        private ContainerInterface $container,
    ) {}

    /**
     * @inheritDoc
     */
    public function resolve(string $name = 'default'): OpaqueTokenManagerInterface
    {
        if (!isset($this->opaqueTokenManagerMap[$name])) {
            throw new \InvalidArgumentException("Opaque Token Manager does not exist: $name");
        }

        $opaqueTokenManagerId = $this->opaqueTokenManagerMap[$name];
        $opaqueTokenManager = $this->container->get($opaqueTokenManagerId);
        if (!$opaqueTokenManager instanceof OpaqueTokenManagerInterface) {
            throw new \LogicException(sprintf('Opaque Token Manager "%s" must implement %s interface', $opaqueTokenManagerId, OpaqueTokenManagerInterface::class));
        }

        return $opaqueTokenManager;
    }
}
