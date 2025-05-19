<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfToken;

use Hyperf\Contract\SessionInterface;

/**
 * 内置的CsrfToken管理器
 * 
 * 依赖于Session
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CsrfTokenManager implements CsrfTokenManagerInterface
{
    public function __construct(private SessionInterface $session) {}

    public function generate(string $tokenId): CsrfToken
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
        return $token->getValue() === $this->session->get(
            $this->getKey($token->getId())
        );
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function getKey(string $tokenId): string
    {
        return sprintf('auth.%s', $tokenId);
    }
}
