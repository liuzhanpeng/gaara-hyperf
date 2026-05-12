<?php

declare(strict_types=1);

namespace GaaraHyperf\Authorization;

use GaaraHyperf\Token\TokenInterface;

/**
 * 内置的空授权检查器.
 *
 * 在没配置授权检查器时，默认使用这个授权检查器
 */
class NullAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function check(TokenInterface $token, mixed $object, mixed $action = null): bool
    {
        if (is_null($token)) {
            return false;
        }

        return true;
    }
}
