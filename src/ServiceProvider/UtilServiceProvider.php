<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use GaaraHyperf\IPResolver\IPResolver;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\IPWhiteListChecker\IPWhiteListChecker;
use GaaraHyperf\IPWhiteListChecker\IPWhiteListCheckerInterface;
use Hyperf\Contract\ContainerInterface;

/**
 * 工具类服务提供者.
 *
 * 注册一些工具类服务, 供认证组件内部使用
 */
class UtilServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // 注册一些工具类服务
        $container->define(IPResolverInterface::class, new IPResolver());
        $container->define(IPWhiteListCheckerInterface::class, new IPWhiteListChecker());
    }
}
