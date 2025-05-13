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
        private string $id,
        private array $params
    ) {}

    /**
     * 返回认证器id
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
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
