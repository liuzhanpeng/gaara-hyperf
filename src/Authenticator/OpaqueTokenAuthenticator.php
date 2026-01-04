<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\Exception\InvalidAccessTokenException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\UserProvider\UserProviderInterface;

/**
 * 不透明令牌认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenAuthenticator extends AbstractAuthenticator
{
    /**
     * @param UserProviderInterface $userProvider
     * @param OpaqueTokenManagerInterface $opaqueTokenManager
     * @param AccessTokenExtractorInterface $accessTokenExtractor
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(
        private UserProviderInterface $userProvider,
        private OpaqueTokenManagerInterface $opaqueTokenManager,
        private AccessTokenExtractorInterface $accessTokenExtractor,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $this->accessTokenExtractor->extractAccessToken($request) !== null;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $accessToken = $this->accessTokenExtractor->extractAccessToken($request);
        if (is_null($accessToken)) {
            throw new InvalidCredentialsException('Access token is missing');
        }

        $token = $this->opaqueTokenManager->resolve($accessToken);
        if (is_null($token)) {
            throw new InvalidAccessTokenException(
                message: 'Invalid access token',
                accessToken: $accessToken,
            );
        }

        return new Passport(
            $token->getUserIdentifier(),
            $this->userProvider->findByIdentifier(...)
        );
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
