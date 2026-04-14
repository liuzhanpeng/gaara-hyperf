<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\OpaqueTokenManager\OpaqueTokenProcessorResolverInterface;
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
        private OpaqueTokenProcessorResolverInterface $opaqueTokenProcessorResolver,
        private string $tokenManager = 'default',
    ) {
    }

    public function handle(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        $opaqueTokenProcessor = $this->opaqueTokenProcessorResolver->resolve($this->tokenManager);

        $opaqueToken = $opaqueTokenProcessor->issue($token);

        return $opaqueTokenProcessor->respond($opaqueToken);
    }
}
