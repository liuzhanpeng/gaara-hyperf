<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 用用户提供者配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com> 
 */
class UserProviderConfig
{
    /**
     * @param string $type
     * @param array $options
     */
    public function __construct(
        private string $type,
        private array $options
    ) {}

    public static function from(array $config): self
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('UserProvider config must be a single array');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        return new self($type, $options);
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
