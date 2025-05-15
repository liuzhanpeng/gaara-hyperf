<?php

declare(strict_types=1);

namespace  Lzpeng\HyperfAuthGuard\Config;

/**
 * 密码哈希器配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordHasherConfig
{
    /**
     * @param string $type
     * @param array $options
     */
    public function __construct(
        private string $type,
        private array $options
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('PasswordHasherConfig must a be a single array');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        return new self($type, $options);
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }
}
