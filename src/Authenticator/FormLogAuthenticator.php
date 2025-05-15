<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 表单登录认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class FormLogAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private array $options
    ) {
        $this->options = array_merge([
            'login_path' => '/login',
            'check_path' => '/check_login',
            'username_parameter' => 'username',
            'password_parameter' => 'password',
        ], $this->options);
    }

    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->checkPath
            && $request->getMethod() === 'POST';
    }

    public function authenticate(ServerRequestInterface $request): Passport {}

    public function createToken(Passport $passport, string $guardName): TokenInterface {}

    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface {}

    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface {}
}
