<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Hyperf\Event\Contract\ListenerInterface;
use Lzpeng\HyperfAuthGuard\Event\EventInterface;

/**
 * 认证守卫过滤监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardFilteredListener implements ListenerInterface
{
    /**
     * @param string $guardName
     * @param ListenerInterface $listener
     */
    public function __construct(
        private string $guardName,
        private ListenerInterface $listener
    ) {}

    /**
     * @inheritDoc
     */
    public function listen(): array
    {
        return $this->listener->listen();
    }

    /**
     * @inheritDoc
     */
    public function process(object $event): void
    {
        if (!$event instanceof EventInterface) {
            throw new \InvalidArgumentException('Event must be instance of ' . EventInterface::class);
        }

        if ($event->getGuardName() === $this->guardName) {
            $this->listener->process($event);
        }
    }
}
