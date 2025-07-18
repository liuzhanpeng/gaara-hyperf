<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 内置的空授权检查器
 * 
 * 在没配置授权检查器时，默认使用这个授权检查器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class NullAuthorizationChecker implements AuthorizationCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function check(TokenInterface $token, string|array $attribute, mixed $subject = null): bool
    {
        if (is_null($token)) {
            return false;
        }

        return true;
    }
}
