<?php

declare(strict_types=1);

namespace GaaraHyperf\Authentication;

use GaaraHyperf\Token\TokenInterface;

/**
 * 认证信任决策器接口.
 */
interface AuthenticationTrustDeciderInterface
{
    /**
     * 判断令牌是否已通过认证.
     */
    public function isAuthenticated(?TokenInterface $token): bool;
}
