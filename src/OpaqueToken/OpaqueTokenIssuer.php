<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use DateTimeInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * 内置的OpaqueToken发行器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenIssuer implements OpaqueTokenIssuerInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $cachePrefix,
    ) {}

    public function issue(TokenInterface $token, ?int $ttl = null): OpaqueToken
    {
        $accessToken = bin2hex(random_bytes(32));
        $this->cache->set($this->getAccessTokenKey($accessToken), $token, $ttl);

        return new OpaqueToken(
            $accessToken,
            (new \DateTimeImmutable())->add(new \DateInterval('PT' . $ttl . 'S'))
        );
    }

    public function revoke(string $accessToken): void
    {
        $this->cache->delete($this->getAccessTokenKey($accessToken));
    }

    public function resolve(string $tokenStr): ?TokenInterface
    {
        return $this->cache->get($this->getAccessTokenKey($tokenStr), null);
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
