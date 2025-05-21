<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceFactory;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuer;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerInterface;

class OpaqueTokenIssuerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(array $config): OpaqueTokenIssuerInterface
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('token_issuer配置必须是单个数组');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        switch ($type) {
            case 'default':
                return $this->container->make(OpaqueTokenIssuer::class, $options);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $opaqueTokenIssuer = $this->container->get($customConfig->class(), $customConfig->args());
                if (!$opaqueTokenIssuer instanceof OpaqueTokenIssuer) {
                    throw new \LogicException(sprintf('%s must be an instance of %s', $customConfig->class(), OpaqueTokenIssuer::class));
                }

                return $opaqueTokenIssuer;
            default:
                throw new \InvalidArgumentException('csrf_token_manager配置类型错误');
        }
    }
}
