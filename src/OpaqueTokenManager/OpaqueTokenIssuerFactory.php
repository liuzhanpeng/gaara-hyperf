<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueTokenManager;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Constants;

/**
 * OpaqueToken管理器创建工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenManagerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @param array $config
     * @return OpaqueTokenManagerInterface
     */
    public function create(array $config): OpaqueTokenManagerInterface
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('token_manager config must have exactly one type defined.');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        switch ($type) {
            case 'default':
                $expiresIn = $options['expires_in'] ?? 60 * 20;
                $maxLifetime = $options['max_lifetime'] ?? 60 * 60 * 24;
                if ($expiresIn > $maxLifetime) {
                    throw new \InvalidArgumentException('The expires_in option must be less than or equal to max_lifetime option.');
                }

                return $this->container->make(OpaqueTokenManager::class, [
                    'prefix' => $options['prefix'] ?? sprintf('%s:%s:', Constants::__PREFIX, 'opaque_token'),
                    'headerParam' => $options['header_param'] ?? 'Authorization',
                    'tokenType' => $options['token_type'] ?? 'Bearer',
                    'expiresIn' => $expiresIn,
                    'maxLifetime' => $maxLifetime,
                    'tokenRefresh' => $options['token_refresh'] ?? true,
                    'ipBindEnabled' => $options['ip_bind_enabled'] ?? false,
                    'userAgentBindEnabled' => $options['user_agent_bind_enabled'] ?? false,
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $opaqueTokenManager = $this->container->get($customConfig->class(), $customConfig->args());
                if (!$opaqueTokenManager instanceof OpaqueTokenManagerInterface) {
                    throw new \LogicException(sprintf('The custom OpaqueTokenManager must implement %s.', OpaqueTokenManagerInterface::class));
                }

                return $opaqueTokenManager;
            default:
                throw new \InvalidArgumentException('Unsupported opaque token manager type: ' . $type);
        }
    }
}
