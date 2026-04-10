<?php

declare(strict_types=1);

namespace GaaraHyperf\Token;

/**
 * 用户令牌接口.
 */
interface TokenInterface
{
    /**
     * 返回发行令牌的认证守卫名称.
     */
    public function getGuardName(): string;

    /**
     * 返回所属用户标识符.
     */
    public function getUserIdentifier(): string;

    /**
     * 是否有属性.
     */
    public function hasAttribute(string $name): bool;

    /**
     * 返回指定属性的值
     */
    public function getAttribute(string $name): mixed;

    /**
     * 设置属性.
     */
    public function setAttribute(string $name, mixed $value): void;
}
