<?php

declare(strict_types=1);

namespace GaaraHyperf\UserProvider;

use GaaraHyperf\User\UserInterface;

/**
 * 用户提供者.
 *
 * 从存储中查找并返回用户
 */
interface UserProviderInterface
{
    /**
     * 通过用户标识查找并返回用户, 如果找不到返回null.
     */
    public function findByIdentifier(string $identifier): ?UserInterface;
}
