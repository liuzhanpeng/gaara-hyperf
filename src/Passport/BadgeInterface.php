<?php

declare(strict_types=1);

namespace GaaraHyperf\Passport;

/**
 * 认证标识.
 *
 * 认证过程中产生的信息，用于认证过程中传递信息
 */
interface BadgeInterface
{
    /**
     * 是否已解决.
     */
    public function isResolved(): bool;
}
