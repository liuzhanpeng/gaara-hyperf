<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;

/**
 * 认证守卫解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardResolver implements GuardResolverInterface
{
    /**
     * @param array<string,string> $guardMap 结构[[guardName => guardId]]
     * @param ContainerInterface $container
     */
    public function __construct(
        private array $guardMap,
        private ContainerInterface $container
    ) {}

    /**
     * @inheritDoc
     */
    public function getGuardNames(): array
    {
        return array_keys($this->guardMap);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $guardName): GuardInterface
    {
        if (!isset($this->guardMap[$guardName])) {
            throw new \InvalidArgumentException("guard id $guardName not found");
        }

        return $this->container->get($this->guardMap[$guardName]);
    }
}
