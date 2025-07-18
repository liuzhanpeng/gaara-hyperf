<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * 服务提供者接口
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
