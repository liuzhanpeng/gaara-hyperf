<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\ConfigLoaderInterface;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\OpaqueTokenManager\OpaqueTokenManagerFactory;
use Lzpeng\HyperfAuthGuard\OpaqueTokenManager\OpaqueTokenManagerInterface;
use Lzpeng\HyperfAuthGuard\OpaqueTokenManager\OpaqueTokenManagerResolver;

/**
 * Opaque Token 管理器服务提供者
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenManagerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $config = $container->get(ConfigLoaderInterface::class)->load();

        $opaqueTokenManagerConfig = array_merge([
            'default' => [
                'type' => 'default',
                'prefix' => sprintf('%s:%s:', Constants::__PREFIX, 'opaque_token'),
                'expires_in' => 60 * 20,
                'max_lifetime' => 60 * 60 * 24,
                'token_refresh' => true,
                'single_session' => true,
                'ip_bind_enabled' => false,
                'user_agent_bind_enabled' => false,
            ],
        ], $config->serviceConfig('opaque_token_issuers') ?? []);

        $opaqueTokenManagerMap = [];
        foreach ($opaqueTokenManagerConfig as $name => $config) {
            $opaqueTokenManagerMap[$name] = sprintf('%s.%s', Constants::OPAQUE_TOKEN_MANAGER_PREFIX, $name);
            $container->define($opaqueTokenManagerMap[$name], fn() => $container->get(OpaqueTokenManagerFactory::class)->create($config));
        }

        $container->define(OpaqueTokenManagerInterface::class, fn() => new OpaqueTokenManagerResolver($opaqueTokenManagerMap, $container));
    }
}
