<?php

declare(strict_types=1);

namespace GaaraHyperf\User;

/**
 * 通过密码认证的用户接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface PasswordAwareUserInterface
{
    /**
     * 返回密码
     *
     * @return string
     */
    public function getPassword(): string;
}
