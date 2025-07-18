<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider;

use Lzpeng\HyperfAuthGuard\User\MemoryUser;
use Lzpeng\HyperfAuthGuard\User\UserInterface;

/**
 * 内存用户提供者
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class MemoryUserProvider implements UserProviderInterface
{
    /**
     * @param array $users
     */
    public function __construct(
        private array $users
    ) {}

    /**
     * @inheritDoc
     */
    public function findByIdentifier(string $identifier): ?UserInterface
    {
        foreach ($this->users as $username => $info) {
            if ($username === $identifier) {
                if (!isset($info['password'])) {
                    throw new \InvalidArgumentException('用户信息中缺少密码字段');
                }

                return new MemoryUser(
                    $username,
                    $info['password'],
                );
            }
        }

        return null;
    }
}
