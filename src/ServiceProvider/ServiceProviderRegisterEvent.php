<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

/**
 * 认证服务注册事件.
 */
class ServiceProviderRegisterEvent
{
    public function __construct(
        private readonly ServiceProviderRegistry $serviceProviderRegistry,
    ) {
    }

    /**
     * 获取服务提供者注册表.
     */
    public function serviceProviderRegistry(): ServiceProviderRegistry
    {
        return $this->serviceProviderRegistry;
    }
}
