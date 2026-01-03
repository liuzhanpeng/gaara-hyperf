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
     * @param array $args 参数
     */
    public function __construct(
        private string $class,
        private array $args
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

        $args = $config['args'] ?? [];
        if (count($args) > 0) {
            $args = array_combine(
                array_map(fn($key) => lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))), array_keys($args)),
                $args
            );
        }

        return new self($config['class'], $args);
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
    public function args(): array
    {
        return $this->args;
    }
}
