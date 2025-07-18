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
class OpaqueTokenResponseHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private OpaqueTokenIssuerResolverInterface $opaqueTokenIssuerResolver,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private string $tokenIssuer = 'default',
        private string $responseTemplate = '{ "code": 0, "msg": "success", "data": { "access_token": "#TOKEN#", "expires_at": #EXPIRES_AT# } }',
    ) {}

    public function handle(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        $opaqueToken = $this->opaqueTokenIssuerResolver->resolve($this->tokenIssuer)->issue($token);

        $result = str_replace(
            ['#TOKEN#', '#EXPIRES_AT#'],
            [$opaqueToken->getTokenStr(), $opaqueToken->getExpiresAt()->getTimestamp()],
            $this->responseTemplate
        );

        return $this->response->json($result);
    }
}
