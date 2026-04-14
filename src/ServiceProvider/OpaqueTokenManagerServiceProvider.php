<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorFactory;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolver;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenProcessor;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenProcessorResolver;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenProcessorResolverInterface;
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
            ],
        ];

        $factories = [];
        foreach ($configGroup as $name => $config) {
            $factories[$name] = fn () => $container->get(OpaqueTokenManagerFactory::class)->create($config);
        }

        $container->set(OpaqueTokenManagerResolverInterface::class, new OpaqueTokenManagerResolver($factories));

        $processorFactories = [];
        foreach ($configGroup as $name => $config) {
            $processorFactories[$name] = function () use ($container, $name, $config): OpaqueTokenProcessor {
                $accessTokenExtractor = $container->get(AccessTokenExtractorFactory::class)->create(
                    ($config['token_extractor'] ?? []) + [
                        'type' => 'header',
                        'field' => 'Authorization',
                        'scheme' => 'Bearer',
                    ]
                );

                $opaqueTokenResponder = $container->get(OpaqueTokenResponderFactory::class)->create(
                    ($config['token_responder'] ?? []) + [
                        'type' => 'body',
                        'template' => '{"code": 0, "message": "success", "data": {"access_token": "#ACCESS_TOKEN#", "expires_in": #EXPIRES_IN#, "user_identifier": "#USER_IDENTIFIER#"}}',
                    ]
                );

                /** @var OpaqueTokenManagerInterface $opaqueTokenManager */
                $opaqueTokenManager = $container->get(OpaqueTokenManagerResolverInterface::class)->resolve($name);

                return new OpaqueTokenProcessor(
                    opaqueTokenManager: $opaqueTokenManager,
                    accessTokenExtractor: $accessTokenExtractor,
                    opaqueTokenResponder: $opaqueTokenResponder,
                );
            };
        }

        $container->set(OpaqueTokenProcessorResolverInterface::class, new OpaqueTokenProcessorResolver($processorFactories));
    }
}
