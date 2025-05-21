<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RquestMatcher;

use Hyperf\Contract\ContainerInterface;

/**
 * 内置的请求匹配器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class RequestMatcherResolver implements RequestMatcherResolverInteface
{
    /**
     * @param array<string,string> $matcherMap 结构: [[guardName => matcherId]]
     * @param ContainerInterface $container
     */
    public function __construct(
        private ContainerInterface $container,
        private array $matcherMap,
    ) {}

    /**
     * @inheritDoc
     */
    public function resolve(string $guardName): RequestMatcherInterface
    {
        if (!isset($this->matcherMap[$guardName])) {
            throw new \InvalidArgumentException("找不到指定guard('{$guardName}')的请求匹配器");
        }

        $matcherId = $this->matcherMap[$guardName];
        $matcher = $this->container->get($matcherId);
        if (!$matcher instanceof RequestMatcherInterface) {
            throw new \LogicException("请求匹配器('{$matcherId}')必须实现RequestMatcherInterface接口");
        }

        return $matcher;
    }
}
