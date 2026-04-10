<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Passport\Passport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证失败处理器接口.
 */
interface AuthenticationFailureHandlerInterface
{
    public function handle(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ResponseInterface;
}
