<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

/**
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueToken
{
    /**
     * @param string $tokenStr
     * @param DateTimeInterface|null $expiresAt
     */
    public function __construct(
        private string $tokenStr,
        private ?\DateTimeInterface $expiresAt = null
    ) {}

    /**
     * 返回token字符串
     *
     * @return string
     */
    public function getTokenStr(): string
    {
        return $this->tokenStr;
    }

    /**
     * 返回过期时间
     *
     * @return \DateTimeInterface|null
     */
    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }
}
