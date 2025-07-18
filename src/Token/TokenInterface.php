<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Token;

use Lzpeng\HyperfAuthGuard\User\UserInterface;

/**
 * 用户令牌接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface TokenInterface
{
    /**
     * 返回发行令牌的认证守卫名称
     *
     * @return string
     */
    public function getGuardName(): string;

    /**
     * 返回所属用户
     *
     * @return UserInterface
     */
    public function getUser(): UserInterface;

    /**
     * 是否有属性
     *
     * @param string $name
     * @return boolean
     */
    public function hasAttribute(string $name): bool;

    /**
     * 返回指定属性的值
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name): mixed;

    /**
     * 设置属性
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setAttribute(string $name, mixed $value): void;
}
