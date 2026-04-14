<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\Token\TokenInterface;

class OpaqueToken
{
    public function __construct(
        private TokenInterface $token,
        private string $accessToken,
        private int $issuedAt,
        private int $expiresIn,
    ) {
    }

    public function token(): TokenInterface
    {
        return $this->token;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function issuedAt(): int
    {
        return $this->issuedAt;
    }

    public function expiresIn(): int
    {
        return $this->expiresIn;
    }

    public function isExpired(): bool
    {
        return $this->issuedAt + $this->expiresIn <= time();
    }
}
