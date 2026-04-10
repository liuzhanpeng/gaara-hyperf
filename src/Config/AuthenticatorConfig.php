<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

/**
 * 认证器配置.
 */
class AuthenticatorConfig
{
    public function __construct(
        private string $type,
        private array $options
    ) {
    }

    /**
     * 返回认证器类型.
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * 返回选项.
     */
    public function options(): array
    {
        return $this->options;
    }
}
