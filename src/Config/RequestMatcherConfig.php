<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 请求匹配器配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class RequestMatcherConfig
{
    public function __construct(
        private string $type,
        private string|array $options
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('RequestMatcher config must be a single array');
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
     * 返回匹配值
     *
     * @return string|array
     */
    public function options(): string|array
    {
        return $this->options;
    }
}
