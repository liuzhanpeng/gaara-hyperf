<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\ServiceProvider\BuiltInAuthenticatorServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\BuiltInUserProviderServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\CsrfTokenManagerServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\GuardServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\PasswordHasherServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\ServiceProviderInterface;
use Lzpeng\HyperfAuthGuard\ServiceProvider\ServiceProviderManager;

/**
 * 认证组件初始化器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthInitializer
{
    /**
     * @var ServiceProviderInterface[]
     */
    private array $providers = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @return void
     */
    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $provider->register($this->container);
        }
    }

    /**
     * 注册服务
     * 
     * 组件调用使用者可以重新注入AuthInitializer实现注册服务, 例:
     * ```php
     * // 在 config/autoload/dependencies.php 中
     * AuthInitializer::class => function (ContainerInterface $container) {
     *     $authInitializer = new AuthInitializer($container);
     *     $authInitializer->registerService(new CustomServiceProvider());
     *     return $authInitializer;
     * }
     * ```
     *
     * @param ServiceProviderInterface $providers
     * @return self
     */
    public function registerService(ServiceProviderInterface $providers): self
    {
        $this->providers[] = $providers;
        return $this;
    }
}
