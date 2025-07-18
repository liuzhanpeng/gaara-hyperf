<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 授权检查器配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthorizationCheckerConfig
{
    /**
     * @param string $class 授权检查器类名
     * @param array $args 授权检查器构造参数
     */
    public function __construct(
        private string $class,
        private array $args,
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
            throw new \InvalidArgumentException('authorization.checker config must have a "class" key');
        }

        return new self(
            $config['class'],
            $config['args'] ?? []
        );
    }

    /**
     * 返回授权检查器类名
     * 
     * @return string
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * 返回授权检查器构造参数
     *
     * @return array
     */
    public function args(): array
    {
        return $this->args;
    }
}
