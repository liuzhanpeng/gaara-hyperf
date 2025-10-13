<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 双因子认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class TwoFactorAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private UserProviderInterface $userProvider,
        private array $options,
    ) {}

    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() ===  $this->options['check_path'];
    }

    public function authenticate(ServerRequestInterface $request, string $guardName): Passport
    {
        $credientials = $this->getCredentials($request);

        return new Passport(
            $guardName,
            $credientials['username'],
            $this->userProvider->findByIdentifier(...),
        );
    }

    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new AuthenticatedToken($guardName, $passport->getUser());
    }

    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        return null;
    }

    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        return null;
    }

    public function isInteractive(): bool
    {
        return true;
    }

    private function getCredentials(ServerRequestInterface $request): array
    {
        return [
            'username' => $request->getParsedBody()['username'] ?? '',
            'code' => $request->getParsedBody()['code'] ?? '',
        ];
    }
}
