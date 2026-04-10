<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

use IteratorAggregate;
use Traversable;

/**
 * 监听器配置集合.
 */
class ListenerConfigCollection implements IteratorAggregate
{
    /**
     * @param CustomConfig[] $listenerConfigCollection
     */
    public function __construct(
        private array $listenerConfigCollection
    ) {
    }

    public static function from(array $config): self
    {
        $listenerConfigCollection = [];
        foreach ($config as $listenerConfig) {
            $listenerConfigCollection[] = CustomConfig::from($listenerConfig);
        }

        return new self($listenerConfigCollection);
    }

    /**
     * @return Traversable<CustomConfig>
     */
    public function getIterator(): Traversable
    {
        yield from $this->listenerConfigCollection;
    }
}
