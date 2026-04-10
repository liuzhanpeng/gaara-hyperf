<?php

declare(strict_types=1);

namespace GaaraHyperf\Passport;

use SensitiveParameter;

/**
 * CSRF令牌认证标识.
 */
class CsrfTokenBadge implements BadgeInterface
{
    /**
     * 是否已被解决.
     */
    private bool $isResolved = false;

    public function __construct(
        private string $id,
        #[SensitiveParameter]
        private string $token
    ) {
        $this->token = $token;
    }

    /**
     * 返回令牌ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 返回CSRF令牌.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * 是否已解决.
     */
    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    /**
     * 设为已解决.
     */
    public function resolve(): void
    {
        $this->isResolved = true;
    }
}
