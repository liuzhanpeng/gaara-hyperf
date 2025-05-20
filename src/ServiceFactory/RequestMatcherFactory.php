<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceFactory;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Config\RequestMatcherConfig;
use Lzpeng\HyperfAuthGuard\RquestMatcher\PatternRequestMatcher;
use Lzpeng\HyperfAuthGuard\RquestMatcher\PrefixRequestMatcher;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherInterface;

/**
 * 请求匹配器服务工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class RequestMatcherFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    /**
     * @param RequestMatcherConfig $requestMatcherConfig
     * @return RequestMatcherInterface
     */
    public function create(RequestMatcherConfig $requestMatcherConfig): RequestMatcherInterface
    {
        $type = $requestMatcherConfig->type();
        $options = $requestMatcherConfig->options();

        switch ($type) {
            case 'pattern':
                if (!isset($options['expr'])) {
                    throw new \InvalidArgumentException('The "expr" option is required for pattern request matcher.');
                }

                return new PatternRequestMatcher($options['expr'], $options['exclusion'] ?? []);
            case 'prefix':
                if (!isset($options['expr'])) {
                    throw new \InvalidArgumentException('The "expr" option is required for prefix request matcher.');
                }

                return new PrefixRequestMatcher($options['expr'], $options['exclusion'] ?? []);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $requestMatcher = $this->container->make($customConfig->class(), $customConfig->args());
                if (!$requestMatcher instanceof RequestMatcherInterface) {
                    throw new \LogicException("自定义匹配器必须实现RequestMatcherInterface接口");
                }

                return $requestMatcher;
            default:
                throw new \InvalidArgumentException("不支持的匹配类型: {$type}");
        }
    }
}
