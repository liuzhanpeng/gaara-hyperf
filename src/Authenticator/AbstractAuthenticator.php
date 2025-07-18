<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 抽象认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
abstract class AbstractAuthenticator implements AuthenticatorInterface
{
    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new AuthenticatedToken($guardName, $passport->getUser());
    }
}
