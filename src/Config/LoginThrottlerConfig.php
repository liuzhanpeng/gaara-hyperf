<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 登录限流器配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LoginThrottlerConfig
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
            throw new \InvalidArgumentException('login_throttler config must be an associative array with a single key-value pair');
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
