<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * JSON Web Token 认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JWTAuthenticator extends OpaqueTokenAuthenticator
{
    public function __construct() {}

    public function supports(RequestInterface $request): bool {}

    public function authenticate(RequestInterface $request, string $guardName): Passport {}

    public function onAuthenticationSuccess(RequestInterface $request, TokenInterface $token): ?ResponseInterface {}

    public function onAuthenticationFailure(RequestInterface $request, AuthenticationException $exception): ?ResponseInterface {}
}
