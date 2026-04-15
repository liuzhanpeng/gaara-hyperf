<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorFactory;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerResolver;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerResolverInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManager;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolver;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderFactory;
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
                'idle_ttl' => 60 * 20,
                'max_ttl' => 60 * 60 * 24,
                'token_refresh' => true,
                'single_session' => true,
                'ip_bind_enabled' => false,
                'user_agent_bind_enabled' => false,
                'token_extractor' => [
                    'type' => 'header',
                    'field' => 'Authorization',
                    'scheme' => 'Bearer',
                ],
                'token_responder' => [
                    'type' => 'body',
                    'template' => '{"code": 0, "message": "success", "data": {"access_token": "#ACCESS_TOKEN#", "expires_in": #EXPIRES_IN#, "user_identifier": "#USER_IDENTIFIER#"}}',
                ],
            ],
        ];

        $factories = [];
        foreach ($configGroup as $name => $config) {
            $factories[$name] = fn () => $container->get(OpaqueTokenIssuerFactory::class)->create($config);
        }

        $container->set(OpaqueTokenIssuerResolverInterface::class, new OpaqueTokenIssuerResolver($factories));

        $managerFactories = [];
        foreach ($configGroup as $name => $config) {
            $managerFactories[$name] = function () use ($container, $name, $config): OpaqueTokenManager {
                $accessTokenExtractor = $container->get(AccessTokenExtractorFactory::class)->create($config['token_extractor']);

                $opaqueTokenResponder = $container->get(OpaqueTokenResponderFactory::class)->create($config['token_responder']);

                /** @var OpaqueTokenIssuerInterface $opaqueTokenIssuer */
                $opaqueTokenIssuer = $container->get(OpaqueTokenIssuerResolverInterface::class)->resolve($name);

                return new OpaqueTokenManager(
                    opaqueTokenIssuer: $opaqueTokenIssuer,
                    accessTokenExtractor: $accessTokenExtractor,
                    opaqueTokenResponder: $opaqueTokenResponder,
                );
            };
        }

        $container->set(OpaqueTokenManagerResolverInterface::class, new OpaqueTokenManagerResolver($managerFactories));
    }
}
