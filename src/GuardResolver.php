<?php

declare(strict_types=1);

namespace GaaraHyperf;

use Generator;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * 认证守卫解析器.
 *
 * 为了避免启动时的循环依赖, 这里会延时加载Guard实例, 否则子组件编写时要考虑依赖注入顺序问题, 增加代码编写难度
 */
class GuardResolver implements IteratorAggregate
{
    /**
     * @var array<string, GuardInterface> 认证守卫工厂列表
     */
    private array $guards = [];

    /**
     * @param array<string, callable> $factories 认证守卫工厂列表
     */
    public function __construct(
        private array $factories,
    ) {
    }

    /**
     * 获取认证守卫.
     */
    public function resolve(string $guardName): GuardInterface
    {
        if (! isset($this->factories[$guardName])) {
            if (! isset($this->factories[$guardName])) {
                throw new InvalidArgumentException(sprintf('guard "%s" not found', $guardName));
            }

            $this->guards[$guardName] = ($this->factories[$guardName])();
        }

        return $this->guards[$guardName];
    }

    /**
     * 返回迭代器.
     *
     * @return Generator<string, GuardInterface>
     */
    public function getIterator(): Generator
    {
        foreach ($this->factories as $guardName => $factory) {
            yield $guardName => $this->resolve($guardName);
        }
    }
}
