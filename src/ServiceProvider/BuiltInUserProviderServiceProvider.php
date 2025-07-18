<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\Builder\MemoryUserProviderBuilder;
use Lzpeng\HyperfAuthGuard\UserProvider\Builder\ModelUserProviderBuilder;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderFactory;

/**
 * 内置用户提供者服务提供者
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class BuiltInUserProviderServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        /**
         * @var UserProviderFactory $userProviderFactory
         */
        $userProviderFactory = $container->get(UserProviderFactory::class);
        $userProviderFactory->registerBuilder('memory', MemoryUserProviderBuilder::class);
        $userProviderFactory->registerBuilder('model', ModelUserProviderBuilder::class);
    }
}
