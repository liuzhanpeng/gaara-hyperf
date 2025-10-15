<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfTokenManager;

use Hyperf\Contract\SessionInterface;

/**
 * 内置的CsrfToken管理器
 * 
 * 依赖于Session
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class SessionCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function __construct(
        private string $prefix,
        private SessionInterface $session,
    ) {}

    /**
     * @inheritDoc
     */
    public function generate(string $tokenId = 'authenticate'): CsrfToken
    {
        $csrfToken = new CsrfToken($tokenId, $this->generateToken());

        $this->session->set(
            $this->getKey($tokenId),
            $csrfToken->getValue()
        );

        return $csrfToken;
    }

    /**
     * @inheritDoc
     */
    public function verify(CsrfToken $token): bool
    {
        return $token->getValue() === $this->session->get(
            $this->getKey($token->getId())
        );
    }

    /**
     * 生成随机令牌
     *
     * @return string
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * 获取存储在Session中的Key
     *
     * @param string $tokenId
     * @return string
     */
    private function getKey(string $tokenId): string
    {
        return sprintf('%s.%s', $this->prefix, $tokenId);
    }
}
