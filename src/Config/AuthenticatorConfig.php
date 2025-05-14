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
    /**
     * @param string $type
     * @param array $params
     */
    public function __construct(
        private string $type,
        private array $params
    ) {}

    /**
     * 返回认证器类型
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * 返回认证器构造参数
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }
}
