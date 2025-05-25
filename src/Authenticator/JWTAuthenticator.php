<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON Web Token 认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JWTAuthenticator extends OpaqueTokenAuthenticator
{
    public function __construct() {}

    public function supports(ServerRequestInterface $request): bool {}

    public function authenticate(ServerRequestInterface $request, string $guardName): Passport {}

    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface {}

    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface {}
}
