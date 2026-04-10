<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

/**
 * 服务提供者注册表.
 */
class ServiceProviderRegistry
{
    /**
     * @var ServiceProviderInterface[]
     */
    private array $providers = [];

    /**
     * 注册服务提供者.
     */
    public function register(ServiceProviderInterface $provider): self
    {
        $this->providers[] = $provider;
        return $this;
    }

    /**
     * 获取所有注册的服务提供者.
     *
     * @return ServiceProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
