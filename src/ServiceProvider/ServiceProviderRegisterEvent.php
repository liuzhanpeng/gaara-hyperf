<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

/**
 * 认证服务注册事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ServiceProviderRegisterEvent
{
    /**
     * @param ServiceProviderRegistry $serviceProviderRegistry
     */
    public function __construct(
        private readonly ServiceProviderRegistry $serviceProviderRegistry,
    ) {}

    /**
     * 获取服务提供者注册表
     *
     * @return ServiceProviderRegistry
     */
    public function ServiceProviderRegistry(): ServiceProviderRegistry
    {
        return $this->serviceProviderRegistry;
    }
}
