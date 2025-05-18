<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\InvalidCredentialsException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Psr\Http\Message\ResponseInterface;

/**
 * JSON登录认证
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JsonLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private array $options,
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private Util $util,
    ) {
        if (!isset($options['check_path'])) {
            throw new \InvalidArgumentException('The "check_path" option must be set.');
        }

        $this->options = array_merge([
            'username_param' => 'username',
            'password_param' => 'password',
        ], $options);
    }

    /**
     * @inheritDoc
     */
    public function supports(RequestInterface $request): bool
    {
        return $this->util->expectJson($request)
            && $request->getPathInfo() === $this->options['check_path']
            && $request->isMethod('POST');
    }

    /**
     * @inheritDoc
     */
    public function authenticate(RequestInterface $request, string $guardName): Passport
    {
        $credientials = $this->getCredentials($request);

        $passport = new Passport(
            $guardName,
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
    public function onAuthenticationSuccess(RequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(RequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        throw $exception;
    }

    /**
     * 获取认证凭证
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getCredentials(RequestInterface $request): array
    {
        $credientials = [];
        $username = $request->post($this->options['username_param'], '');
        if (!is_string($username) || empty($username)) {
            throw new InvalidCredentialsException('username must be string.');
        }
        $credientials['username'] = trim($username);

        $password = $request->post($this->options['password_param'], '');
        if (!is_string($password) || empty($password)) {
            throw new InvalidCredentialsException('password must be string.');
        }
        $credientials['password'] = trim($password);

        return $credientials;
    }
}
