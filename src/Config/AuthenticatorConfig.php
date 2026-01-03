<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

/**
 * 认证器配置
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatorConfig
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
     * 返回认证器类型
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * 返回选项
     *
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }
}
