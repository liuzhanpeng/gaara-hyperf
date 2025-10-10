<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;

/**
 * 认证初始化监听器
 * 
 * 监听框架启动事件，初始化认证组件
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthInitListener implements ListenerInterface
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
            BeforeMainServerStart::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(object $event): void
    {
        $initializer = new AuthInitializer($this->container);
        $initializer->init();
    }
}
