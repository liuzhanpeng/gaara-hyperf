<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\ConfigLoaderInterface;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\OpaqueTokenIssuer\OpaqueTokenIssuerFactory;
use Lzpeng\HyperfAuthGuard\OpaqueTokenIssuer\OpaqueTokenIssuerResolver;
use Lzpeng\HyperfAuthGuard\OpaqueTokenIssuer\OpaqueTokenIssuerResolverInterface;

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
                    'prefix' => sprintf('%s:%s:', Constants::__PREFIX, 'opaque_token'),
                    'header_param' => 'Authorization',
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 20,
                    'max_lifetime' => 60 * 60 * 24,
                    'token_refresh' => true,
                    'ip_bind_enabled' => false,
                    'user_agent_bind_enabled' => false,
                ],
            ],
        ], $config->serviceConfig('opaque_token_issuers') ?? []);

        $opaqueTokenIssuerMap = [];
        foreach ($opaqueTokenIssuerConfig as $name => $config) {
            $opaqueTokenIssuerMap[$name] = sprintf('%s.%s', Constants::OPAQUE_TOKEN_ISSUER_PREFIX, $name);
            $container->define($opaqueTokenIssuerMap[$name], fn() => $container->get(OpaqueTokenIssuerFactory::class)->create($config));
        }

        $container->define(OpaqueTokenIssuerResolverInterface::class, fn() => new OpaqueTokenIssuerResolver($opaqueTokenIssuerMap, $container));
    }
}
