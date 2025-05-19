<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Passport;

class CsrfTokenBadge implements BadgeInterface
{
    private bool $isResolved = false;

    public function __construct(
        private string $id,
        #[\SensitiveParameter]
        private string $token
    ) {
        $this->token = $token;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    public function resolve(): void
    {
        $this->isResolved = true;
    }
}
