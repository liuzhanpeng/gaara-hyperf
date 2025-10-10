<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\User;

/**
 * 双因子认证用户接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface TwoFactorUserInterface
{
    /**
     * 是否启用双因子认证
     *
     * @return boolean
     */
    public function isTwoFactorEnabled(): bool;

    /**
     * 获取双因子认证代码
     *
     * @return string
     */
    public function getTwoFactorCode(): string;
}
