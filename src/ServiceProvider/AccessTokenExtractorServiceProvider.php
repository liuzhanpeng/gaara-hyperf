<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\AccessTokenExtractor\AccessTokenExtractorFactory;
use Lzpeng\HyperfAuthGuard\AccessTokenExtractor\AccessTokenExtractorResolver;
use Lzpeng\HyperfAuthGuard\AccessTokenExtractor\AccessTokenExtractorResolverInterface;
use Lzpeng\HyperfAuthGuard\Config\ConfigLoaderInterface;
use Lzpeng\HyperfAuthGuard\Constants;

/**
 * Access Token 提取器服务提供者
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AccessTokenExtractorServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $config = $container->get(ConfigLoaderInterface::class)->load();

        $accessTokenExtractorConfig = array_merge([
            'default' => [
                'type' => 'header',
                'param_name' => 'Authorization',
                'param_type' => 'Bearer',
            ]
        ], $config->serviceConfig('access_token_extractors') ?? []);

        $accessTokenExtractorMap = [];
        foreach ($accessTokenExtractorConfig as $name => $config) {
            $accessTokenExtractorMap[$name] = sprintf('%s.%s', Constants::ACCESS_TOKEN_EXTRACTOR_PREFIX, $name);
            $container->define($accessTokenExtractorMap[$name], fn() => $container->get(AccessTokenExtractorFactory::class)->create($config));
        }

        $container->define(AccessTokenExtractorResolverInterface::class, fn() => new AccessTokenExtractorResolver($accessTokenExtractorMap, $container));
    }
}
