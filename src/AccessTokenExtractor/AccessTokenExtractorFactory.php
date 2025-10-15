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
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('access_token_extractor config must have exactly one type defined.');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        switch ($type) {
            case 'header':
                return $this->container->make(HeaderAccessTokenExtractor::class, [
                    'param' => $options['param'] ?? 'Authorization',
                    'type' => $options['type'] ?? 'Bearer',
                ]);
            case 'cookie':
                return $this->container->make(CookieAccessTokenExtractor::class, [
                    'param' => $options['param'] ?? 'access_token',
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($options);

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
