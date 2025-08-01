<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerResolverInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 不透明令牌响应处理器
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private OpaqueTokenIssuerResolverInterface $opaqueTokenIssuerResolver,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private string $tokenIssuer = 'default',
    ) {}

    public function handle(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        $opaqueToken = $this->opaqueTokenIssuerResolver->resolve($this->tokenIssuer)->issue($token);

        return $this->response->json([
            'access_token' => $opaqueToken->getTokenStr(),
            'expires_at' => $opaqueToken->getExpiresAt()->getTimestamp(),
        ]);
    }
}
