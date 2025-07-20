<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\OpaqueToken\CacheOpaqueTokenIssuer;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerInterface;

class OpaqueTokenIssuerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(array $config): OpaqueTokenIssuerInterface
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('token_issuer config must have exactly one type defined.');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        switch ($type) {
            case 'cache':
                return $this->container->make(CacheOpaqueTokenIssuer::class, [
                    'prefix' => $options['prefix'] ?? 'auth:opaque_token:',
                    'ttl' => $options['ttl'] ?? 60 * 20
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $opaqueTokenIssuer = $this->container->get($customConfig->class(), $customConfig->args());
                if (!$opaqueTokenIssuer instanceof OpaqueTokenIssuerInterface) {
                    throw new \LogicException(sprintf('The custom OpaqueTokenIssuer must implement %s.', OpaqueTokenIssuerInterface::class));
                }

                return $opaqueTokenIssuer;
            default:
                throw new \InvalidArgumentException('Unsupported opaque token issuer type: ' . $type);
        }
    }
}
