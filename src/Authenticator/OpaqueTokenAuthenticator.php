<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Lzpeng\HyperfAuthGuard\OpaqueTokenIssuer\OpaqueTokenIssuerInterface;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 不透明令牌认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private OpaqueTokenIssuerInterface $tokenIssuer,
    ) {}

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
    public function authenticate(ServerRequestInterface $request, string $guardName): Passport
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
            $guardName,
            $token->getUser()->getIdentifier(),
            fn() => $token->getUser(),
            []
        );
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        throw $exception;
    }

    public function isInteractive(): bool
    {
        return false;
    }
}
