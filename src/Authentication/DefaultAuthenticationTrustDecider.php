<?php

declare(strict_types=1);

namespace GaaraHyperf\Authentication;

use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\Token\TokenInterface;

/**
 * 默认认证信任决策器.
 */
class DefaultAuthenticationTrustDecider implements AuthenticationTrustDeciderInterface
{
    public function isAuthenticated(?TokenInterface $token): bool
    {
        return $token instanceof AuthenticatedToken;
    }
}
