<?php

declare(strict_types=1);

namespace GaaraHyperf\User;

/**
 * 用户接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface UserInterface
{
    /**
     * 返回用户标识符
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * 清除令牌中的敏感信息
     *
     * @return void
     */
    public function eraseCredentials(): void;
}
