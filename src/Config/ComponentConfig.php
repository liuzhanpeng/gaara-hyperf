<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 内部组件通用配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ComponentConfig
{
    /**
     * @param string $type
     * @param array $options
     */
    public function __construct(
        private string $type,
        private array $options = []
    ) {}

    /**
     * @param array $config
     * @param string $default
     * @return self
     */
    public static function from(array $config, string $default = ''): self
    {
        if (!isset($config['type']) && empty($default)) {
            throw new \InvalidArgumentException('type is required for component config');
        }

        $type = $config['type'] ?? $default;
        unset($config['type']);

        return new self($type, $config);
    }

    /**
     * 返回类型
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * 返回参数
     * 
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }
}
