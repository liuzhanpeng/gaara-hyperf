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
    /**
     * @param CustomConfig[] $listenerConfigCollection
     */
    public function __construct(
        private array $listenerConfigCollection
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        $listenerConfigCollection = [];
        foreach ($config as $listenerConfig) {
            $listenerConfigCollection[] = CustomConfig::from($listenerConfig);
        }

        return new self($listenerConfigCollection);
    }

    /**
     * @inheritDoc
     * 
     * @return Traversable<CustomConfig>
     */
    public function getIterator(): Traversable
    {
        yield from $this->listenerConfigCollection;
    }
}
