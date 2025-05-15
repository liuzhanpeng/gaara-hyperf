<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider;

use Lzpeng\HyperfAuthGuard\User\UserInterface;

/**
 * 用户提供者
 * 
 * 从存储中查找并返回用户
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface UserProviderInterface
{
    /**
     * 通过用户标识查找并返回用户, 如果找不到返回null
     * 
     * @param string $identifier
     * @return UserInterface|null
     */
    public function findByIdentifier(string $identifier): ?UserInterface;
}
