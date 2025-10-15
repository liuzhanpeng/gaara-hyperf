<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\OpaqueTokenManager;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\Utils\IpResolver;
use Psr\SimpleCache\CacheInterface;

/**
 * 内置的OpaqueToken管理器
 * 
 * 基于缓存实现
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class DefaultOpaqueTokenManager implements OpaqueTokenManagerInterface
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
     * @param boolean $singleSession
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
        private bool $singleSession,
        private bool $ipBindEnabled,
        private bool $userAgentBindEnabled,
    ) {}

    /**
     * @inheritDoc
     */
    public function issue(TokenInterface $token): string
    {
        $accessToken = bin2hex(random_bytes(32));
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

        if ($this->singleSession) {
            $this->cache->delete($this->getUserTokenKey($token->getUser()->getIdentifier()));
        }

        $this->cache->set($this->getAccessTokenKey($accessToken), $data, $this->expiresIn);
        $this->cache->set($this->getUserTokenKey($token->getUser()->getIdentifier()), $accessToken, $this->maxLifetime);

        return $accessToken;
    }


    /**
     * @inheritDoc
     */
    public function resolve(string $accessToken): ?TokenInterface
    {
        $data = $this->cache->get($this->getAccessTokenKey($accessToken));
        if (is_null($data)) {
            return null;
        }

        if ($data['expires_at'] < time()) {
            $this->revoke($accessToken);
            return null;
        }

        if ($this->ipBindEnabled && (!isset($data['ip']) || $data['ip'] !== $this->ipResolver->resolve($this->request))) {
            return null;
        }
        if ($this->userAgentBindEnabled && (!isset($data['user_agent']) || $data['user_agent'] !== md5($this->request->getHeaderLine('User-Agent')))) {
            return null;
        }

        if ($this->tokenRefresh) {
            $this->cache->set($this->getAccessTokenKey($accessToken), $data, $this->expiresIn);
        }

        return $data['token'];
    }

    /**
     * @inheritDoc
     */
    public function revoke(string $accessToken): void
    {
        $this->cache->delete($this->getAccessTokenKey($accessToken));
        if ($this->singleSession) {
            $data = $this->cache->get($this->getAccessTokenKey($accessToken));
            if (!is_null($data)) {
                $this->cache->delete($this->getUserTokenKey($data['token']->getUser()->getIdentifier()));
            }
        }
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

    /**
     * 返回用户Token键
     *
     * @param string $identifier
     * @return string
     */
    private function getUserTokenKey(string $identifier): string
    {
        return sprintf('%s:user:%s', $this->prefix, $identifier);
    }
}
