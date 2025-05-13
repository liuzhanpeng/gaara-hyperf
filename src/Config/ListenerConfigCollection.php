<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

use Traversable;

/**
 * 监听器配置集合
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ListenerConfigCollection implements \IteratorAggregate
{
    public function __construct(private array $listenerClasses) {}

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->listenerClasses;
    }
}
