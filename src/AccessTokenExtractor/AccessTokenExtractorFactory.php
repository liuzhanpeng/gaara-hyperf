<?php

declare(strict_types=1);

namespace GaaraHyperf\AccessTokenExtractor;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Config\CustomConfig;

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
                    'field' => $config['field'] ?? 'Authorization',
                    'scheme' => $config['scheme'] ?? 'Bearer',
                ]);
            case 'cookie':
                return $this->container->make(CookieAccessTokenExtractor::class, [
                    'field' => $config['field'] ?? 'access_token',
                ]);
            case 'body':
                return $this->container->make(BodyAccessTokenExtractor::class, [
                    'field' => $config['field'] ?? 'access_token',
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($config);

                $accessTokenExtractor = $this->container->make($customConfig->class(), $customConfig->params());
                if (!$accessTokenExtractor instanceof AccessTokenExtractorInterface) {
                    throw new \LogicException(sprintf('The custom AccessTokenExtractor must implement %s.', AccessTokenExtractorInterface::class));
                }

                return $accessTokenExtractor;
            default:
                throw new \InvalidArgumentException("Access Token Extractor type does not exist: $type");
        }
    }
}
