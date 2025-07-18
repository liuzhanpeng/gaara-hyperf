<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\TokenStorage;

use Hyperf\Contract\SessionInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 基于Session的TokenStorage实现
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class SessionTokenStorage implements TokenStorageInterface
{
    /**
     * @param SessionInterface $session
     * @param string $prefix
     */
    public function __construct(
        private SessionInterface $session,
        private string $prefix = 'auth.token',
    ) {}

    /**
     * @inheritDoc
     */
    public function get(string $key): ?TokenInterface
    {
        return $this->session->get($this->getKey($key));
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, TokenInterface $token): void
    {
        $this->session->set($this->getKey($key), $token);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        $this->session->remove($this->getKey($key));
    }

    /**
     * 返回令牌存储的key
     *
     * @param string $key
     * @return string
     */
    private function getKey(string $key): string
    {
        return sprintf('%s.%s', $this->prefix, $key);
    }
}
