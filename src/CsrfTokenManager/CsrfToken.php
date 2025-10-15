<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfTokenManager;

/**
 * CSRF令牌
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CsrfToken implements \Stringable
{
    /**
     * @param string $id
     * @param string $value
     */
    public function __construct(
        private string $id,
        #[\SensitiveParameter]
        private string $value,
    ) {}

    /**
     * 返回ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 返回值
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
