<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * 内置的OpaqueToken发行器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CacheOpaqueTokenIssuer implements OpaqueTokenIssuerInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $cachePrefix,
        private ?int $ttl = null, // 令牌的有效期，单位为秒
    ) {}

    public function issue(TokenInterface $token): OpaqueToken
    {
        $accessToken = bin2hex(random_bytes(32));
        $this->cache->set($this->getAccessTokenKey($accessToken), $token, $this->ttl);

        return new OpaqueToken(
            $accessToken,
            (new \DateTimeImmutable())->add(new \DateInterval('PT' . $this->ttl . 'S'))
        );
    }

    public function revoke(string $accessToken): void
    {
        $this->cache->delete($this->getAccessTokenKey($accessToken));
    }

    public function resolve(string $tokenStr, bool $refresh = true): ?TokenInterface
    {
        $token = $this->cache->get($this->getAccessTokenKey($tokenStr));
        if (!is_null($token) && $refresh) {
            $this->cache->set($this->getAccessTokenKey($tokenStr), $token, $this->ttl);
        }

        return $token;
    }

    /**
     * 返回AccessToken键
     *
     * @param string $accessToken
     * @return string
     */
    private function getAccessTokenKey(string $accessToken): string
    {
        return sprintf('%s:%s', $this->cachePrefix, $accessToken);
    }
}
