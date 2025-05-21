<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\User;

interface PasswordForceChangeUserInterface
{
    /**
     * 是否需要强制修改密码
     *
     * @return bool
     */
    public function needsForcePasswordChange(): bool;
}
