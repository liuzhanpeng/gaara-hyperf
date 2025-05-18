<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

class OpaqueTokenResponseHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private array $options,
        private OpaqueTokenIssuerInterface $opaqueTokenIssuer,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
    ) {
        $this->options = array_merge([
            'token_name' => 'token',
            'expires_in' => 3600,
        ], $this->options);
    }

    public function handle(RequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        $opaqueToken = $this->opaqueTokenIssuer->issue(
            $token,
            (new \DateTimeImmutable())->add(new \DateInterval('PT' . $this->options['expires_in'] . 'S'))
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
