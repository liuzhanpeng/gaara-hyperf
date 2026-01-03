<?php

declare(strict_types=1);

namespace GaaraHyperf;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\ServiceProvider\AccessTokenExtractorServiceProvider;
use GaaraHyperf\ServiceProvider\ServiceProviderRegisterEvent;
use GaaraHyperf\ServiceProvider\BuiltInAuthenticatorServiceProvider;
use GaaraHyperf\ServiceProvider\BuiltInUserProviderServiceProvider;
use GaaraHyperf\ServiceProvider\CsrfTokenManagerServiceProvider;
use GaaraHyperf\ServiceProvider\GuardServiceProvider;
use GaaraHyperf\ServiceProvider\OpaqueTokenManagerServiceProvider;
use GaaraHyperf\ServiceProvider\PasswordHasherServiceProvider;
use GaaraHyperf\ServiceProvider\ServiceProviderRegistry;
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
