<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\AccessTokenExtractor;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;

/**
 * 访问令牌提取器工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AccessTokenExtractorFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(array $config): AccessTokenExtractorInterface
    {
        $type = $config['type'] ?? 'header';
        unset($config['type']);

        switch ($type) {
            case 'header':
                return $this->container->make(HeaderAccessTokenExtractor::class, [
                    'paramName' => $config['param_name'] ?? 'Authorization',
                    'paramType' => $config['param_type'] ?? 'Bearer',
                ]);
            case 'cookie':
                return $this->container->make(CookieAccessTokenExtractor::class, [
                    'paramName' => $config['param_name'] ?? 'access_token',
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($config);

                $accessTokenExtractor = $this->container->get($customConfig->class(), $customConfig->args());
                if (!$accessTokenExtractor instanceof AccessTokenExtractorInterface) {
                    throw new \LogicException(sprintf('The custom AccessTokenExtractor must implement %s.', AccessTokenExtractorInterface::class));
                }

                return $accessTokenExtractor;
            default:
                throw new \InvalidArgumentException("Access Token Extractor type does not exist: $type");
        }
    }
}
