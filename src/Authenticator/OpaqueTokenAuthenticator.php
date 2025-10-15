<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\AccessTokenExtractor\AccessTokenExtractorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Lzpeng\HyperfAuthGuard\OpaqueTokenManager\OpaqueTokenManagerInterface;
use Lzpeng\HyperfAuthGuard\Passport\Passport;

/**
 * 不透明令牌认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private OpaqueTokenManagerInterface $tokenManager,
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
            throw new UnauthenticatedException();
        }

        $token = $this->tokenManager->resolve($accessToken);
        if (is_null($token)) {
            throw new UnauthenticatedException();
        }

        return new Passport(
            $token->getUser()->getIdentifier(),
            fn() => $token->getUser(),
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
