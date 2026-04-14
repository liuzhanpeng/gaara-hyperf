<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

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
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    public function supports(ServerRequestInterface $request): bool
    {
        return $this->opaqueTokenManager->getExtractor()->extract($request) !== null;
    }

    public function authenticate(ServerRequestInterface $request): Passport
    {
        $accessToken = $this->opaqueTokenManager->getExtractor()->extract($request);
        if (is_null($accessToken)) {
            throw new InvalidCredentialsException('Access token is missing');
        }

        $opaqueToken = $this->opaqueTokenManager->resolve($accessToken);
        if (is_null($opaqueToken)) {
            throw new InvalidCredentialsException('Invalid access token', $accessToken);
        }

        if ($opaqueToken->isExpired()) {
            $this->opaqueTokenManager->revoke($accessToken);
            throw new InvalidCredentialsException('Access token has expired', $accessToken);
        }

        $token = $opaqueToken->token();

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
