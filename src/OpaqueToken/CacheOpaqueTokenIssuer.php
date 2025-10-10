<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueToken;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\Utils\IpResolver;
use Psr\SimpleCache\CacheInterface;

/**
 * 内置的OpaqueToken发行器
 * 
 * 基于缓存实现
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CacheOpaqueTokenIssuer implements OpaqueTokenIssuerInterface
{
    /**
     * @param CacheInterface $cache
     * @param RequestInterface $request
     * @param IpResolver $ipResolver
     * @param string $prefix
     * @param string $headerParam
     * @param string $tokenType
     * @param integer $expiresIn
     * @param integer $maxLifetime
     * @param boolean $tokenRefresh
     * @param boolean $ipBindEnabled
     * @param boolean $userAgentBindEnabled
     */
    public function __construct(
        private CacheInterface $cache,
        private RequestInterface $request,
        private IpResolver $ipResolver,
        private string $prefix,
        private string $headerParam,
        private string $tokenType,
        private int $expiresIn,
        private int $maxLifetime,
        private bool $tokenRefresh,
        private bool $ipBindEnabled,
        private bool $userAgentBindEnabled,
    ) {}

    /**
     * @inheritDoc
     */
    public function issue(TokenInterface $token): OpaqueToken
    {
        $accessToken = bin2hex(random_bytes(32));
        $expiresAt =  (new \DateTimeImmutable())->add(new \DateInterval('PT' . $this->expiresIn . 'S'));

        $time = time();
        $data = [
            'token' => $token,
            'issued_at' => $time,
            'expires_at' => $time + $this->maxLifetime,
        ];

        if ($this->ipBindEnabled) {
            $data['ip'] = $this->ipResolver->resolve($this->request);
        }

        if ($this->userAgentBindEnabled) {
            $data['user_agent'] = md5($this->request->getHeaderLine('User-Agent'));
        }

        $this->cache->set($this->getAccessTokenKey($accessToken), $data, $this->expiresIn);

        return new OpaqueToken($accessToken, $expiresAt);
    }

    /**
     * @inheritDoc
     */
    public function revoke(string $accessToken): void
    {
        $this->cache->delete($this->getAccessTokenKey($accessToken));
    }

    /**
     * @inheritDoc
     */
    public function resolve(): ?TokenInterface
    {
        $tokenStr = $this->extractAccessToken();
        if (is_null($tokenStr)) {
            return null;
        }

        $data = $this->cache->get($this->getAccessTokenKey($tokenStr));
        if (is_null($data)) {
            return null;
        }

        if ($data['expires_at'] < time()) {
            $this->revoke($tokenStr);
            return null;
        }

        if ($this->ipBindEnabled && (!isset($data['ip']) || $data['ip'] !== $this->ipResolver->resolve($this->request))) {
            return null;
        }
        if ($this->userAgentBindEnabled && (!isset($data['user_agent']) || $data['user_agent'] !== md5($this->request->getHeaderLine('User-Agent')))) {
            return null;
        }

        if ($this->tokenRefresh) {
            $this->cache->set($this->getAccessTokenKey($tokenStr), $data, $this->expiresIn);
        }

        return $data['token'];
    }

    /**
     * 提取AccessToken
     *
     * @return string|null
     */
    private function extractAccessToken(): ?string
    {
        if (!$this->request->hasHeader($this->headerParam) || !\is_string($header = $this->request->getHeaderLine($this->headerParam))) {
            return null;
        }

        $regex = \sprintf(
            '/^%s([a-zA-Z0-9\-_\+~\/\.]+=*)$/',
            '' === $this->tokenType ? '' : preg_quote($this->tokenType) . '\s+'
        );

        if (preg_match($regex, $header, $matches)) {
            return $matches[1];
        }

        return null;
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
