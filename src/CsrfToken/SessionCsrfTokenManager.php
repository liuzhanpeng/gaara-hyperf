<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfToken;

use Hyperf\Contract\SessionInterface;
use Hyperf\HttpServer\Contract\RequestInterface;

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
        return sprintf('%s.%s', $this->prefix, $tokenId);
    }
}
