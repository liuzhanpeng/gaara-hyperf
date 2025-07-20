<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

use DateTimeImmutable;
use Hyperf\HttpServer\Contract\RequestInterface;
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
        private RequestInterface $request,
        private string $prefix,
        private int $ttl,
    ) {}

    public function issue(TokenInterface $token): OpaqueToken
    {
        $accessToken = bin2hex(random_bytes(32));
        $expiresAt =  (new \DateTimeImmutable())->add(new \DateInterval('PT' . $this->ttl . 'S'));

        $this->cache->set($this->getAccessTokenKey($accessToken), $token, $this->ttl);

        return new OpaqueToken($accessToken, $expiresAt);
    }

    public function revoke(string $accessToken): void
    {
        $this->cache->delete($this->getAccessTokenKey($accessToken));
    }

    public function resolve(string $tokenStr, bool $refresh = true): ?TokenInterface
    {
        $token = $this->cache->get($this->getAccessTokenKey($tokenStr));
        if (is_null($token)) {
            return null;
        }


        if ($refresh) {
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
        return sprintf('%s:%s', $this->prefix, $accessToken);
    }
}
