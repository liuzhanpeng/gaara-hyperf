<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerFactory;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolver;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolverInterface;
use Hyperf\Contract\ContainerInterface;

/**
 * CSRF令牌管理器服务提供者.
 */
class CsrfTokenManagerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $gaaraConfig = $container->get(ConfigLoaderInterface::class)->load();
        $configGroup = ($gaaraConfig->serviceConfig('csrf_token_managers') ?? []) + [
            'default' => [
                'type' => 'session',
                'prefix' => 'default',
            ],
        ];

        $factories = [];
        foreach ($configGroup as $name => $config) {
            $factories[$name] = fn () => $container->get(CsrfTokenManagerFactory::class)->create($config);
        }

        $container->define(CsrfTokenManagerResolverInterface::class, new CsrfTokenManagerResolver($factories));
    }
}
