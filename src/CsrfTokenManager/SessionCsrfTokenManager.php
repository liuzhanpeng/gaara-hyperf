<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

use Hyperf\Contract\SessionInterface;

/**
 * 内置的CsrfToken管理器.
 *
 * 依赖于Session
 */
class SessionCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function __construct(
        private string $prefix,
        private SessionInterface $session,
    ) {
    }

    public function generate(string $tokenId = 'authenticate'): CsrfToken
    {
        $csrfToken = new CsrfToken($tokenId, $this->generateToken());

        $this->session->set(
            $this->getKey($tokenId),
            $csrfToken->getValue()
        );

        return $csrfToken;
    }

    public function verify(CsrfToken $token): bool
    {
        $key = $this->getKey($token->getId());
        $tokenValue = $this->session->get($key);

        $this->session->remove($key);

        return $token->getValue() === $tokenValue;
    }

    /**
     * 生成随机令牌.
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * 获取存储在Session中的Key.
     */
    private function getKey(string $tokenId): string
    {
        return sprintf('%s.%s', $this->prefix, $tokenId);
    }
}
