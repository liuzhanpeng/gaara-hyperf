<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Psr\Http\Message\ResponseInterface;

/**
 * JSON登录认证
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JsonLoginAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private string $checkPath,
        private string $usernameParam,
        private string $passwordParam,
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private Util $util,
    ) {}

    /**
     * @inheritDoc
     */
    public function supports(RequestInterface $request): bool
    {
        return $this->util->expectJson($request)
            && $request->getPathInfo() === $this->checkPath
            && $request->isMethod('POST');
    }

    /**
     * @inheritDoc
     */
    public function authenticate(RequestInterface $request): Passport
    {
        $credientials = $this->getCredentials($request);

        $passport = new Passport(
            $credientials['username'],
            $this->userProvider->findByIdentifier(...),
            [
                new PasswordBadge($credientials['password']),
            ]
        );

        return $passport;
    }

    /**
     * @inheritDoc
     */
    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new AuthenticatedToken($guardName, $passport->getUser());
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(RequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (is_null($this->successHandler)) {
            return null;
        }

        return $this->successHandler->handle($request, $token);
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(RequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (is_null($this->failureHandler)) {
            throw $exception;
        }

        return $this->failureHandler->handle($request, $exception);
    }

    /**
     * 获取认证凭证
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getCredentials(RequestInterface $request): array
    {
        return [
            'username' => $request->input($this->usernameParam),
            'password' => $request->input($this->passwordParam),
        ];
    }
}
