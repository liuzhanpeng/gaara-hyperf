<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\Constants;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerFactory;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolver;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolverInterface;

/**
 * CSRF令牌管理器服务提供者
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CsrfTokenManagerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $config = $container->get(ConfigLoaderInterface::class)->load();

        $csrfTokenManagerConfig = array_merge([
            'default' => [
                'type' => 'session',
                'prefix' => 'default',
            ],
        ], $config->serviceConfig('csrf_token_managers') ?? []);

        $csrfTokenManagerMap = [];
        foreach ($csrfTokenManagerConfig as $name => $config) {
            $csrfTokenManagerMap[$name] = sprintf('%s.%s', Constants::CSRF_TOKEN_MANAGER_PREFIX, $name);
            $container->define($csrfTokenManagerMap[$name], fn() => $container->get(CsrfTokenManagerFactory::class)->create($config));
        }

        $container->define(CsrfTokenManagerResolverInterface::class, fn() => new CsrfTokenManagerResolver($csrfTokenManagerMap, $container));
    }
}
