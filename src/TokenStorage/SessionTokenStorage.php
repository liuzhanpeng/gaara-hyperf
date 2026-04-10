<?php

declare(strict_types=1);

namespace GaaraHyperf\TokenStorage;

use GaaraHyperf\Token\TokenInterface;
use Hyperf\Contract\SessionInterface;

/**
 * 基于Session的TokenStorage实现.
 */
class SessionTokenStorage implements TokenStorageInterface
{
    public function __construct(
        private SessionInterface $session,
        private string $prefix,
    ) {
    }

    public function get(string $key): ?TokenInterface
    {
        return $this->session->get($this->getKey($key));
    }

    public function set(string $key, TokenInterface $token): void
    {
        $this->session->set($this->getKey($key), $token);
    }

    public function delete(string $key): void
    {
        $this->session->remove($this->getKey($key));
    }

    /**
     * 返回令牌存储的key.
     */
    private function getKey(string $key): string
    {
        return sprintf('%s:%s', $this->prefix, $key);
    }
}
