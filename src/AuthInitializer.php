<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\ServiceProvider\AccessTokenExtractorServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\ServiceProviderRegisterEvent;
use Lzpeng\HyperfAuthGuard\ServiceProvider\BuiltInAuthenticatorServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\BuiltInUserProviderServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\CsrfTokenManagerServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\GuardServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\OpaqueTokenManagerServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\PasswordHasherServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\ServiceProviderRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 认证组件初始化器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthInitializer
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * 初始化认证组件
     *
     * @return void
     */
    public function init(): void
    {
        $serviceProviderRegistry = new ServiceProviderRegistry();

        // 注册内置服务提供者
        $serviceProviderRegistry
            ->register(new BuiltInAuthenticatorServiceProvider())
            ->register(new BuiltInUserProviderServiceProvider())
            ->register(new PasswordHasherServiceProvider())
            ->register(new CsrfTokenManagerServiceProvider())
            ->register(new OpaqueTokenManagerServiceProvider())
            ->register(new AccessTokenExtractorServiceProvider())
            ->register(new GuardServiceProvider());

        /**
         * @var EventDispatcherInterface $eventDispatcher
         */
        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        // 分发认证服务注册事件，允许用户注册自定义的服务提供者
        $eventDispatcher->dispatch(new ServiceProviderRegisterEvent($serviceProviderRegistry));

        foreach ($serviceProviderRegistry->getProviders() as $provider) {
            $provider->register($this->container);
        }
    }
}
