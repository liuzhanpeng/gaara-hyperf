<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 监听器配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ListenerConfig
{
    public function __construct(
        private string $class,
        private array $args,
    ) {}

    /**
     * @param string|array $config
     * @return self
     */
    public static function from(string|array $config): self
    {
        if (is_string($config)) {
            return new self($config, []);
        }

        return new self($config['class'], $config['args'] ?? []);
    }

    /**
     * 监听器类名
     *
     * @return string
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * 监听器构造参数
     *
     * @return array
     */
    public function args(): array
    {
        return $this->args;
    }
}
