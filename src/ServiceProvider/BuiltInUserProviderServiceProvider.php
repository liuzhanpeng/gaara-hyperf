<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use GaaraHyperf\UserProvider\Builder\MemoryUserProviderBuilder;
use GaaraHyperf\UserProvider\Builder\ModelUserProviderBuilder;
use GaaraHyperf\UserProvider\UserProviderFactory;
use Hyperf\Contract\ContainerInterface;

/**
 * 内置用户提供者服务提供者.
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
