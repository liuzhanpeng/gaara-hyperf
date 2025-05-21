<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\Factory\AuthenticatorFactoryInterface;

/**
 * 认证器注册器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatorRegistry
{
    /**
     * 已注册的的认证器工厂
     *
     * @var array<string, string>
     */
    private array $factories = [];

    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * 注册认证器工厂方法
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
     * 是否存在认证器工厂
     *
     * @param string $type
     * @return boolean
     */
    public function hasFactory(string $type): bool
    {
        return isset($this->factories[$type]);
    }

    /**
     * 返回认证器工厂
     *
     * @param string $type
     * @return AuthenticatorFactoryInterface|null
     */
    public function getFacotry(string $type): AuthenticatorFactoryInterface
    {
        if (!$this->hasFactory($type)) {
            throw new \InvalidArgumentException("认证器工厂不存在: $type");
        }

        $authenticatorFactory = $this->container->get($this->factories[$type]);
        if (!$authenticatorFactory instanceof AuthenticatorFactoryInterface) {
            throw new \InvalidArgumentException("认证器工厂必须实现 AuthenticatorFactoryInterface 接口: $type");
        }

        return $authenticatorFactory;
    }
}
