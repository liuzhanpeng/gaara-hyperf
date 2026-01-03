<?php

declare(strict_types=1);

namespace GaaraHyperf\User;

/**
 * 密码过期用户接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface PasswordExpirationAwareUserInterface
{
    /**
     * 返回密码过期时间
     *
     * @return \DateTimeInterface
     */
    public function getExpiresAt(): \DateTimeInterface;
}
