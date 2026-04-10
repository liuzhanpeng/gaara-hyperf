<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

use InvalidArgumentException;

/**
 * 自定义扩展配置.
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
    ) {
    }

    public static function from(array|string $config): self
    {
        if (is_string($config)) {
            return new self($config, []);
        }

        if (! isset($config['class'])) {
            throw new InvalidArgumentException('class is required');
        }

        $params = $config['params'] ?? [];
        if (count($params) > 0) {
            $params = array_combine(
                array_map(fn ($key) => lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))), array_keys($params)),
                $params
            );
        }

        return new self($config['class'], $params);
    }

    /**
     * 返回类名.
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * 返回参数.
     */
    public function params(): array
    {
        return $this->params;
    }
}
