<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use Hyperf\Contract\ContainerInterface;

/**
 * 服务提供者接口.
 *
 * 通过实现该接口, 可以向认证组件注册服务
 */
interface ServiceProviderInterface
{
    /**
     * 注册服务
     */
    public function register(ContainerInterface $container): void;
}
