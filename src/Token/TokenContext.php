<?php

declare(strict_types=1);

namespace GaaraHyperf\Token;

use Hyperf\Context\Context;

/**
 * 内置的用户令牌上下文.
 */
class TokenContext implements TokenContextInterface
{
    public function __construct(private string $prefix)
    {
    }

    public function getToken(): ?TokenInterface
    {
        return Context::get($this->getKey('token'));
    }

    public function setToken(?TokenInterface $token): void
    {
        Context::set($this->getKey('token'), $token);
    }

    /**
     * 返回令牌存储的键.
     */
    private function getKey(string $key): string
    {
        return sprintf('%s.%s', $this->prefix, $key);
    }
}
