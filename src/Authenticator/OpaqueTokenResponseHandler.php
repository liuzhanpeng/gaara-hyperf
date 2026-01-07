<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
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

    public function handle(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        $accessToken = $this->opaqueTokenManagerResolver->resolve($this->tokenManager)->issue($token);

        $template = $this->responseTemplate ?? '{"access_token": "#ACCESS_TOKEN#"}';
        if (!is_string($template) || !is_array(json_decode($template, true))) {
            throw new \InvalidArgumentException('Response template must be a valid JSON string');
        }

        $responseData = json_decode(str_replace('#ACCESS_TOKEN#', (string)$accessToken, $template), true);

        return $this->response->json($responseData);
    }
}
