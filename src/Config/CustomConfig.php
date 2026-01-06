<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

/**
 * 自定义扩展配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CustomConfig
{
    /**
     * @param string $class 扩展类名
     * @param array $params 参数
     */
    public function __construct(
        private string $class,
        private array $params
    ) {}

    /**
     * @param array|string $config
     * @return self
     */
    public static function from(array|string $config): self
    {
        if (is_string($config)) {
            return new self($config, []);
        }

        if (!isset($config['class'])) {
            throw new \InvalidArgumentException('class is required');
        }

        return new self($config['class'], $config['params'] ?? []);
    }

    /**
     * 返回类名
     *
     * @return string
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * 返回参数
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }
}
