<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\AccessTokenExtractor;

use Hyperf\Contract\ContainerInterface;

/**
 * 访问令牌提取器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AccessTokenExtractorResolver implements AccessTokenExtractorResolverInterface
{
    /**
     * @param array $accessTokenExtractorMap
     * @param ContainerInterface $container
     */
    public function __construct(
        private array $accessTokenExtractorMap,
        private ContainerInterface $container,
    ) {}

    /**
     * @inheritDoc
     */
    public function resolve(string $name = 'header'): AccessTokenExtractorInterface
    {
        if (!isset($this->accessTokenExtractorMap[$name])) {
            throw new \InvalidArgumentException("Access Token Extractor does not exist: $name");
        }

        $accessTokenExtractorId = $this->accessTokenExtractorMap[$name];
        $accessTokenExtractor = $this->container->get($accessTokenExtractorId);
        if (!$accessTokenExtractor instanceof AccessTokenExtractorInterface) {
            throw new \LogicException(sprintf('Access Token Extractor "%s" must implement %s interface', $accessTokenExtractorId, AccessTokenExtractorInterface::class));
        }

        return $accessTokenExtractor;
    }
}
