<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

class OpaqueTokenResponseHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private OpaqueTokenIssuerInterface $opaqueTokenIssuer,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private array $options = [],
    ) {
        $this->options = array_merge([
            'token_name' => 'token',
            'expires_in' => 3600,
        ], $this->options);
    }

    public function handle(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        $opaqueToken = $this->opaqueTokenIssuer->issue(
            $token,
            $this->options['expires_in'],
        );

        return $this->response->json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'access_token' => $opaqueToken->getTokenStr(),
                'expires_at' => $opaqueToken->getExpiresAt()->getTimestamp(),
            ],
        ]);
    }
}
