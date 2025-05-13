<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

/**
 * 认证监听器
 * 
 * 根据配置向DI容器中注册各个认证子组件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthListener implements ListenerInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @inheritDoc
     */
    public function listen(): array
    {
        return [
            BootApplication::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(object $event): void
    {
        $serviceProvider = $this->container->get(ServiceProvider::class);

        $serviceProvider->register();
    }
}
