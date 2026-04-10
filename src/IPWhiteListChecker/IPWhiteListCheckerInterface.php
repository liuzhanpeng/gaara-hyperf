<?php

declare(strict_types=1);

namespace GaaraHyperf\IPWhiteListChecker;

/**
 * IP白名单检查器.
 */
interface IPWhiteListCheckerInterface
{
    /**
     * 检查ip是否在白名单内.
     */
    public function isAllowed(string $ip, array $whiteList): bool;
}
