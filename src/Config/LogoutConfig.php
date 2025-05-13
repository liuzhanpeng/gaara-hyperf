<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 登出配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LogoutConfig
{
    public function __construct(
        private string $path,
        private string $target,
        private array $handlers
    ) {}

    public static function from(array $config): self
    {
        if (!isset($config['path']) || !isset($config['target'])) {
            throw new \InvalidArgumentException('参数错误');
        }

        return new self(
            $config['path'],
            $config['target'],
            $config['handlers'] ?? []
        );
    }

    /**
     * 返回登出路径
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * 登出成功后跳转的页面
     *
     * @return string
     */
    public function target(): string
    {
        return $this->target;
    }

    /**
     * 返回登出处理器
     *
     * @return array
     */
    public function handlers(): array
    {
        return $this->handlers;
    }
}
