<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
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
        private OpaqueTokenManagerResolverInterface $opaqueTokenManagerResolver,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private string $tokenManager = 'default',
        private ?string $responseTemplate = null,
    ) {}

    public function handle(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        $accessToken = $this->opaqueTokenManagerResolver->resolve($this->tokenManager)->issue($token);

        if (!is_null($this->responseTemplate)) {
            if (!is_string($this->responseTemplate) || !is_array(json_decode($this->responseTemplate, true))) {
                throw new \InvalidArgumentException('Response template must be a valid JSON string');
            }

            $responseData = json_decode(str_replace('#ACCESS_TOKEN#', (string)$accessToken, $this->responseTemplate), true);

            return $this->response->json($responseData);
        }

        return $this->response->json([
            'access_token' => $accessToken,
        ]);
    }
}
