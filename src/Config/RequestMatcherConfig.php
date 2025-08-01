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
    /**
     * @param string $pattern
     * @param string|null $logoutPath
     * @param array $exclusions
     */
    public function __construct(
        private string $pattern,
        private ?string $logoutPath,
        private array $exclusions
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        if (!isset($config['pattern'])) {
            throw new \InvalidArgumentException('pattern is required in request matcher config');
        }

        return new self($config['pattern'], $config['logout_path'] ?? null, $config['exclusions'] ?? []);
    }

    /**
     * 获取匹配的路径模式
     *
     * @return string
     */
    public function pattern(): string
    {
        return $this->pattern;
    }

    /**
     * 获取注销路径
     *
     * @return string|null
     */
    public function logoutPath(): string|null
    {
        return $this->logoutPath;
    }

    /**
     * 获取排除的路径
     *
     * @return array
     */
    public function exclusions(): array
    {
        return $this->exclusions;
    }
}
