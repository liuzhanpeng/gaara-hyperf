<?php

declare(strict_types=1);

namespace GaaraHyperf\User;

use DateTimeInterface;

/**
 * 密码过期用户接口.
 */
interface PasswordExpirationAwareUserInterface
{
    /**
     * 返回密码过期时间.
     */
    public function getExpiresAt(): DateTimeInterface;
}
