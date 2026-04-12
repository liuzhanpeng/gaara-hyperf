<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 不透明令牌认证器.
 */
class OpaqueTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private OpaqueTokenManagerInterface $opaqueTokenManager,
        private AccessTokenExtractorInterface $accessTokenExtractor,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    public function supports(ServerRequestInterface $request): bool
    {
        return $this->accessTokenExtractor->extract($request) !== null;
    }

    public function authenticate(ServerRequestInterface $request): Passport
    {
        $accessToken = $this->accessTokenExtractor->extract($request);
        if (is_null($accessToken)) {
            throw new InvalidCredentialsException('Access token is missing');
        }

        $token = $this->opaqueTokenManager->resolve($accessToken);
        if (is_null($token)) {
            throw new InvalidCredentialsException('Invalid access token', $accessToken);
        }

        return new Passport(
            $token->getUserIdentifier(),
            $this->userProvider->findByIdentifier(...)
        );
    }

    public function isInteractive(): bool
    {
        return false;
    }
}
