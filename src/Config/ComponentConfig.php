<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

use InvalidArgumentException;

/**
 * 内部组件通用配置.
 */
class ComponentConfig
{
    public function __construct(
        private string $type,
        private array $options = []
    ) {
    }

    public static function from(array $config, string $default = ''): self
    {
        if (! isset($config['type']) && empty($default)) {
            throw new InvalidArgumentException('type is required for component config');
        }

        $type = $config['type'] ?? $default;
        unset($config['type']);

        return new self($type, $config);
    }

    /**
     * 返回类型.
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * 返回参数.
     */
    public function options(): array
    {
        return $this->options;
    }
}
