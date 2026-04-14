<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorFactory;
use GaaraHyperf\Config\CustomConfig;
use GaaraHyperf\Constants;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderFactory;
use Hyperf\Contract\ContainerInterface;
use InvalidArgumentException;

/**
 * OpaqueToken管理器创建工厂
 */
class OpaqueTokenManagerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function create(array $config): OpaqueTokenManagerInterface
    {
        $type = $config['type'] ?? 'default';
        unset($config['type']);

        switch ($type) {
            case 'default':
                $accessTokenExtractorFactory = $this->container->make(AccessTokenExtractorFactory::class);
                $accessTokenExtractor = $accessTokenExtractorFactory->create(
                    ($config['token_extractor'] ?? []) + [
                        'type' => 'header',
                        'field' => 'Authorization',
                        'scheme' => 'Bearer',
                    ]
                );

                $opaqueTokenResponderFactory = $this->container->make(OpaqueTokenResponderFactory::class);
                $opaqueTokenResponder = $opaqueTokenResponderFactory->create(
                    ($config['token_responder'] ?? []) + [
                        'type' => 'body',
                        'template' => '{"code": 0, "message": "success", "data": {"access_token": "#ACCESS_TOKEN#", "expires_in": #EXPIRES_IN#, "user_identifier": "#USER_IDENTIFIER#"}}',
                    ]
                );

                return $this->container->make(OpaqueTokenManager::class, [
                    'accessTokenExtractor' => $accessTokenExtractor,
                    'opaqueTokenResponder' => $opaqueTokenResponder,
                    'prefix' => sprintf('%s:opaque_token:%s', Constants::__PREFIX, $config['prefix'] ?? 'default'),
                    'idleTtl' => $config['idle_ttl'] ?? 60 * 20,
                    'maxTtl' => $config['max_ttl'] ?? 60 * 60 * 24,
                    'tokenRefresh' => $config['token_refresh'] ?? true,
                    'singleSession' => $config['single_session'] ?? true,
                    'ipBindEnabled' => $config['ip_bind_enabled'] ?? false,
                    'userAgentBindEnabled' => $config['user_agent_bind_enabled'] ?? false,
                    'accessTokenLength' => $config['access_token_length'] ?? 64,
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($config);

                $opaqueTokenManager = $this->container->make($customConfig->class(), $customConfig->params());
                if (! $opaqueTokenManager instanceof OpaqueTokenManagerInterface) {
                    throw new InvalidArgumentException(sprintf('The custom OpaqueTokenManager must implement %s.', OpaqueTokenManagerInterface::class));
                }

                return $opaqueTokenManager;
            default:
                throw new InvalidArgumentException('Unsupported opaque token manager type: ' . $type);
        }
    }
}
