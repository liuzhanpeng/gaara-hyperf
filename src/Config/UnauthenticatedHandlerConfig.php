<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 未认证处理器配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UnauthenticatedHandlerConfig
{
    /**
     * @param string $class
     * @param array $params
     */
    public function __construct(
        private string $class,
        private array $params,
    ) {}

    /**
     * @param array|string $config
     * @return self
     */
    public static function from(array $config): self
    {
        if (is_string($config)) {
            return new self($config, []);
        }

        if (!isset($config['class'])) {
            throw new \InvalidArgumentException('unauthenticated_handler配置中缺少class属性');
        }

        return new self(
            $config['class'],
            $config['params'] ?? []
        );
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
     * 返回构造参数
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }
}
