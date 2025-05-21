<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerInterface;

/**
 * 登出配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LogoutConfig
{
    /**
     * @param string $path 登出路径
     * @param string|null $targetPath 登出成功后跳转的页面 
     */
    public function __construct(
        private string $path,
        private ?string $targetPath,
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        if (!isset($config['path'])) {
            throw new \InvalidArgumentException('logout path is required');
        }

        return new self(
            $config['path'],
            $config['target_path'] ?? null,
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
     * @return string|null
     */
    public function targetPath(): string|null
    {
        return $this->targetPath;
    }
}
