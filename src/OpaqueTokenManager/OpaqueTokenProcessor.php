<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderInterface;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OpaqueTokenProcessor implements OpaqueTokenProcessorInterface
{
    public function __construct(
        private OpaqueTokenManagerInterface $opaqueTokenManager,
        private AccessTokenExtractorInterface $accessTokenExtractor,
        private OpaqueTokenResponderInterface $opaqueTokenResponder,
    ) {
    }

    public function extract(ServerRequestInterface $request): ?string
    {
        return $this->accessTokenExtractor->extract($request);
    }

    public function issue(TokenInterface $token): OpaqueToken
    {
        return $this->opaqueTokenManager->issue($token);
    }

    public function resolve(string $accessToken): ?OpaqueToken
    {
        return $this->opaqueTokenManager->resolve($accessToken);
    }

    public function revoke(string $accessToken): void
    {
        $this->opaqueTokenManager->revoke($accessToken);
    }

    public function respond(OpaqueToken $opaqueToken): ResponseInterface
    {
        return $this->opaqueTokenResponder->respond($opaqueToken);
    }
}
