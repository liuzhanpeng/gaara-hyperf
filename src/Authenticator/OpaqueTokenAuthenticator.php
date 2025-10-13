<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Lzpeng\HyperfAuthGuard\OpaqueTokenIssuer\OpaqueTokenIssuerInterface;
use Lzpeng\HyperfAuthGuard\Passport\Passport;

/**
 * 不透明令牌认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private OpaqueTokenIssuerInterface $tokenIssuer,
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
        return $this->tokenIssuer->extractAccessToken($request) !== null;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $accessTokenStr = $this->tokenIssuer->extractAccessToken($request);
        if (is_null($accessTokenStr)) {
            throw new UnauthenticatedException();
        }

        $token = $this->tokenIssuer->resolve($accessTokenStr);
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
