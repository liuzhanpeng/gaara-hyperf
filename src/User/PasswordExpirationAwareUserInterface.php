<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\User;

/**
 * 密码过期用户接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface PasswordExpirationAwareUserInterface
{
    /**
     * 密码是否已过期
     *
     * @return boolean
     */
    public function isPasswordExpired(): bool;

    /**
     * 返回密码过期时间
     *
     * @return \DateTimeInterface
     */
    public function getExpiresAt(): \DateTimeInterface;
}
