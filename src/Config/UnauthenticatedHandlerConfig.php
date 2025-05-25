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
     * @param string $type
     * @param array $options
     */
    public function __construct(
        private string $type,
        private array $options,
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('unauthenticated_handler配置必须是单个数组');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        return new self($type, $options);
    }

    /**
     * 返回匹配类型
     * 
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * 返回选项
     *
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }
}
