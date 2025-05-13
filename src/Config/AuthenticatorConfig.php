<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 认证器配置
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatorConfig
{
    public function __construct(
        private string $name,
        private array $params
    ) {}

    /**
     * 返回认证器id
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * 返回认证器参数
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }
}
