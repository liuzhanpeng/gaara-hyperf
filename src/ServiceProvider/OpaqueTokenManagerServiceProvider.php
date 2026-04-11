<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolver;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use Hyperf\Contract\ContainerInterface;

/**
 * Opaque Token 管理器服务提供者.
 */
class OpaqueTokenManagerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $gaaraConfig = $container->get(ConfigLoaderInterface::class)->load();

        $configGroup = ($gaaraConfig->serviceConfig('opaque_token_managers') ?? []) + [
            'default' => [
                'type' => 'default',
                'prefix' => 'default',
                'ttl' => 60 * 20,
                'max_ttl' => 60 * 60 * 24,
                'token_refresh' => true,
                'single_session' => true,
                'ip_bind_enabled' => false,
                'user_agent_bind_enabled' => false,
            ],
        ];

        $factories = [];
        foreach ($configGroup as $name => $config) {
            $factories[$name] = fn () => $container->get(OpaqueTokenManagerFactory::class)->create($config);
        }

        $container->define(OpaqueTokenManagerResolverInterface::class, new OpaqueTokenManagerResolver($factories));
    }
}
