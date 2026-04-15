<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderInterface;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OpaqueTokenManager implements OpaqueTokenManagerInterface
{
    public function __construct(
        private OpaqueTokenIssuerInterface $opaqueTokenIssuer,
        private AccessTokenExtractorInterface $accessTokenExtractor,
        private OpaqueTokenResponderInterface $opaqueTokenResponder,
    ) {
    }

    public function issue(TokenInterface $token): ResponseInterface
    {
        $opaqueToken = $this->opaqueTokenIssuer->issue($token);
        return $this->opaqueTokenResponder->respond($opaqueToken);
    }

    public function resolve(ServerRequestInterface $request): ?OpaqueToken
    {
        $accessToken = $this->accessTokenExtractor->extract($request);
        if ($accessToken === null) {
            return null;
        }

        return $this->opaqueTokenIssuer->resolve($accessToken);
    }

    public function revoke(ServerRequestInterface $request): void
    {
        $accessToken = $this->accessTokenExtractor->extract($request);
        if ($accessToken === null) {
            return;
        }

        $this->opaqueTokenIssuer->revoke($accessToken);
    }
}
