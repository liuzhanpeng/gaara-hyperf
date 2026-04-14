<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderInterface;
use GaaraHyperf\Token\TokenInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * 内置的OpaqueToken管理器.
 *
 * 基于缓存实现
 */
class OpaqueTokenManager implements OpaqueTokenManagerInterface
{
    public function __construct(
        private AccessTokenExtractorInterface $accessTokenExtractor,
        private OpaqueTokenResponderInterface $opaqueTokenResponder,
        private CacheInterface $cache,
        private RequestInterface $request,
        private IPResolverInterface $ipResolver,
        private string $prefix,
        private int $idleTtl,
        private int $maxTtl,
        private bool $tokenRefresh,
        private bool $singleSession,
        private bool $ipBindEnabled,
        private bool $userAgentBindEnabled,
        private int $accessTokenLength
    ) {
        if ($this->accessTokenLength < 32) {
            throw new InvalidArgumentException('Access token length must be at least 32 characters.');
        }

        if ($this->idleTtl > $this->maxTtl) {
            throw new InvalidArgumentException('The idle_ttl option must be less than or equal to max_ttl option.');
        }
    }

    public function issue(TokenInterface $token): OpaqueToken
    {
        $accessToken = bin2hex(random_bytes($this->accessTokenLength / 2));
        $time = time();
        $data = [
            'token' => $token,
            'issued_at' => $time,
            'expires_in' => $this->maxTtl,
        ];

        if ($this->ipBindEnabled) {
            $data['ip'] = $this->ipResolver->resolve($this->request);
        }

        if ($this->userAgentBindEnabled) {
            $data['user_agent'] = md5($this->request->getHeaderLine('User-Agent'));
        }

        if ($this->singleSession) {
            $preAccessToken = $this->cache->get($this->getUserKey($token->getUserIdentifier()));
            if (! is_null($preAccessToken)) {
                $this->cache->delete($this->getAccessTokenKey($preAccessToken));
            }

            $this->cache->set($this->getUserKey($token->getUserIdentifier()), $accessToken, $this->maxTtl);
        }

        $this->cache->set($this->getAccessTokenKey($accessToken), $data, $this->idleTtl);

        return new OpaqueToken($token, $accessToken, $time, $this->maxTtl);
    }

    public function resolve(string $accessToken): ?OpaqueToken
    {
        $data = $this->cache->get($this->getAccessTokenKey($accessToken));
        if (is_null($data)) {
            return null;
        }

        if ($this->ipBindEnabled && (! isset($data['ip']) || $data['ip'] !== $this->ipResolver->resolve($this->request))) {
            return null;
        }
        if ($this->userAgentBindEnabled && (! isset($data['user_agent']) || $data['user_agent'] !== md5($this->request->getHeaderLine('User-Agent')))) {
            return null;
        }

        if ($this->tokenRefresh) {
            $this->cache->set($this->getAccessTokenKey($accessToken), $data, $this->idleTtl);
        }

        return new OpaqueToken($data['token'], $accessToken, $data['issued_at'], $data['expires_in']);
    }

    public function revoke(string $accessToken): void
    {
        if ($this->singleSession) {
            $data = $this->cache->get($this->getAccessTokenKey($accessToken));
            if (! is_null($data)) {
                $this->cache->delete($this->getUserKey($data['token']->getUserIdentifier()));
            }
        }

        $this->cache->delete($this->getAccessTokenKey($accessToken));
    }

    public function getExtractor(): AccessTokenExtractorInterface
    {
        return $this->accessTokenExtractor;
    }

    public function getResponder(): OpaqueTokenResponderInterface
    {
        return $this->opaqueTokenResponder;
    }

    /**
     * 返回AccessToken键.
     */
    private function getAccessTokenKey(string $accessToken): string
    {
        return sprintf('%s:%s', $this->prefix, $accessToken);
    }

    /**
     * 返回用户键.
     */
    private function getUserKey(string $identifier): string
    {
        return sprintf('%s:user:%s', $this->prefix, $identifier);
    }
}
