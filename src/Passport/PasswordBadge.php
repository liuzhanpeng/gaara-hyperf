<?php

declare(strict_types=1);

namespace GaaraHyperf\Passport;

use SensitiveParameter;

/**
 * 密码凭证标识.
 */
class PasswordBadge implements BadgeInterface
{
    /**
     * 是否已被解决.
     */
    private bool $isResolved = false;

    public function __construct(
        #[SensitiveParameter]
        private string $password
    ) {
    }

    /**
     * 返回密码
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * 设为已解决.
     */
    public function resolve(): void
    {
        $this->isResolved = true;
    }

    public function isResolved(): bool
    {
        return $this->isResolved;
    }
}
