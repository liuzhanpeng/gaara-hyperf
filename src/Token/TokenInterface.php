<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Token;

use Lzpeng\HyperfAuthGuard\User\UserInterface;

/**
 * 用户令牌接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface TokenInterface extends \Stringable
{
    /**
     * 返回认证守卫ID
     *
     * @return string
     */
    public function getGuardId(): string;

    /**
     * 返回用户
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface;

    /**
     * 设置用户
     *
     * @param UserInterface $user
     * @return void
     */
    public function setUser(UserInterface $user): void;

    /**
     * 是否有属性
     *
     * @param string $name
     * @return boolean
     */
    public function hasAttrubute(string $name): bool;

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
