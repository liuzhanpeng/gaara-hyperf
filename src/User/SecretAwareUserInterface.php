<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\User;

/**
 * 需要提供密钥的用户接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface SecretAwareUserInterface
{
    /**
     * 获取用户的密钥
     *
     * @return string
     */
    public function getSecret(): string;
}
