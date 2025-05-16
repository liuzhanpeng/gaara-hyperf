<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * API Key认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ApiKeyAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private string $apiKeyParam,
        private UserProviderInterface $userProvider,
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {}

    public function supports(RequestInterface $request): bool
    {
        return !empty($request->getHeaderLine($this->apiKeyParam));
    }

    public function authenticate(RequestInterface $request): Passport
    {
        $apiKey = $request->getHeaderLine($this->apiKeyParam);

        return new Passport(
            $apiKey,
            $this->userProvider->findByIdentifier(...),
            []
        );
    }

    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new AuthenticatedToken($guardName, $passport->getUser());
    }

    public function onAuthenticationSuccess(RequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        return null;
    }

    public function onAuthenticationFailure(RequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        throw $exception;
    }
}
