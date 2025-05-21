<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\Factory\UserProviderFactoryInterface;

/**
 * 用户提供者注册器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UserProviderRegistry
{
    /**
     * 已注册的用户提供者工厂
     *
     * @var array
     */
    private array $factories = [];

    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * 注册用户提供者工厂
     *
     * @param string $type
     * @param string $factory
     * @return void
     */
    public function register(string $type, string $factory): void
    {
        $this->factories[$type] = $factory;
    }

    /**
     * 是否存在用户提供者工厂
     *
     * @param string $type
     * @return boolean
     */
    public function hasFactory(string $type): bool
    {
        return isset($this->factories[$type]);
    }

    /**
     * 获取用户提供者工厂
     *
     * @param string $type
     * @return UserProviderFactoryInterface
     */
    public function getFactory(string $type): UserProviderFactoryInterface
    {
        if (!$this->hasFactory($type)) {
            throw new \InvalidArgumentException(sprintf('用户提供者工厂 "%s" 不存在', $type));
        }

        $factory = $this->container->get($this->factories[$type]);

        return $factory;
    }
}
