<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 不透明令牌响应处理器.
 */
class OpaqueTokenResponseHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private OpaqueTokenManagerResolverInterface $opaqueTokenManagerResolver,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private string $tokenManager = 'default',
        private ?string $responseTemplate = null,
    ) {
    }

    public function handle(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        $accessToken = $this->opaqueTokenManagerResolver->resolve($this->tokenManager)->issue($token);

        $template = str_replace(
            ['#ACCESS_TOKEN#', '#USER_IDENTIFIER#'],
            [$accessToken, $token->getUserIdentifier()],
            $this->responseTemplate ?? '{"user_identifier": "#USER_IDENTIFIER#", "access_token": "#ACCESS_TOKEN#"}',
        );

        if (! is_string($template) || ! is_array(json_decode($template, true))) {
            throw new InvalidArgumentException('Response template must be a valid JSON string');
        }

        return $this->response->json(json_decode($template, true));
    }
}
