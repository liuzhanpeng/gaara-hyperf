<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 不透明令牌响应处理器.
 */
class OpaqueTokenResponseHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private OpaqueTokenManagerResolverInterface $opaqueTokenManagerResolver,
        private string $tokenManager = 'default',
    ) {
    }

    public function handle(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        $opaqueTokenManager = $this->opaqueTokenManagerResolver->resolve($this->tokenManager);

        $opaqueToken = $opaqueTokenManager->issue($token);

        return $opaqueTokenManager->getResponder()->respond($opaqueToken);
    }
}
