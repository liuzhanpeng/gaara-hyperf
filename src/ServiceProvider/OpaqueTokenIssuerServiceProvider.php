<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\ConfigLoaderInterface;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerFactory;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerResolver;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerResolverInterface;

/**
 * Opaque Token 发行器服务提供者
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenIssuerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $config = $container->get(ConfigLoaderInterface::class)->load();

        $opaqueTokenIssuerConfig = array_merge([
            'default' => [
                'cache' => [
                    'prefix' => 'auth:opaque_token',
                    'ttl' => 60 * 20,
                ],
            ],
        ], $config->serviceConfig('opaque_token_issuers') ?? []);

        $opaqueTokenIssuerMap = [];
        foreach ($opaqueTokenIssuerConfig as $name => $config) {
            $opaqueTokenIssuerMap[$name] = sprintf('%s.%s', Constants::OPAQUE_TOKEN_ISSUER_PREFIX, $name);
            $container->define($opaqueTokenIssuerMap[$name], function () use ($container, $config) {
                return $container->get(OpaqueTokenIssuerFactory::class)->create($config);
            });
        }

        $container->define(OpaqueTokenIssuerResolverInterface::class, function () use ($container, $opaqueTokenIssuerMap) {
            return new OpaqueTokenIssuerResolver($opaqueTokenIssuerMap, $container);
        });
    }
}
