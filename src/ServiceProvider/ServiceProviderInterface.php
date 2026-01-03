<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use Hyperf\Contract\ContainerInterface;

/**
 * 服务提供者接口
 * 
 * 通过实现该接口, 可以向认证组件注册服务
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface ServiceProviderInterface
{
    /**
     * 注册服务
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container): void;
}
