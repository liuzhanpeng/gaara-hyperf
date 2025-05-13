<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

class NullAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function check(?TokenInterface $token, string|array $attribute, mixed $subject): bool
    {
        return true;
    }
}
