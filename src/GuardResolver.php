<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Psr\Container\ContainerInterface;

/**
 * 认证守卫解析器
 * 
 * 这里会延时加载Guard实例，为了避免启动时的循环依赖; 否则子组件编写时要考虑依赖注入顺序问题, 增加编写难度
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardResolver implements \IteratorAggregate
{
    /**
     * @param array<string,string> $guardMap 结构[guardName => guardId, ...]
     * @param ContainerInterface $container
     */
    public function __construct(
        private array $guardMap,
        private ContainerInterface $container,
    ) {}

    /**
     * 获取认证守卫
     *
     * @param string $guardName
     * @return GuardInterface
     */
    public function resolve(string $guardName): GuardInterface
    {
        if (!isset($this->guardMap[$guardName])) {
            throw new \InvalidArgumentException(sprintf('guard "%s" not found', $guardName));
        }

        return $this->container->get($this->guardMap[$guardName]);
    }

    /**
     * 返回迭代器
     *
     * @return \Generator<string, GuardInterface>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->guardMap as $guardName => $guardId) {
            yield $guardName => $this->container->get($guardId);
        }
    }
}
