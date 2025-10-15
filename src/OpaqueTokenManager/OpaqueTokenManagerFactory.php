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
        $type = $config['type'] ?? 'default';
        unset($config['type']);

        switch ($type) {
            case 'default':
                $expiresIn = $config['expires_in'] ?? 60 * 20;
                $maxLifetime = $config['max_lifetime'] ?? 60 * 60 * 24;
                if ($expiresIn > $maxLifetime) {
                    throw new \InvalidArgumentException('The expires_in option must be less than or equal to max_lifetime option.');
                }

                return $this->container->make(DefaultOpaqueTokenManager::class, [
                    'prefix' => sprintf('%s:opaque_token:%s', Constants::__PREFIX, $config['prefix'] ?? 'default'),
                    'expiresIn' => $expiresIn,
                    'maxLifetime' => $maxLifetime,
                    'tokenRefresh' => $config['token_refresh'] ?? true,
                    'singleSession' => $config['single_session'] ?? true,
                    'ipBindEnabled' => $config['ip_bind_enabled'] ?? false,
                    'userAgentBindEnabled' => $config['user_agent_bind_enabled'] ?? false,
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($config);

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
