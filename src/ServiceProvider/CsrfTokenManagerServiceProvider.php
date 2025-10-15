<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\ConfigLoaderInterface;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\CsrfTokenManager\CsrfTokenManagerFactory;
use Lzpeng\HyperfAuthGuard\CsrfTokenManager\CsrfTokenManagerResolver;
use Lzpeng\HyperfAuthGuard\CsrfTokenManager\CsrfTokenManagerResolverInterface;

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
