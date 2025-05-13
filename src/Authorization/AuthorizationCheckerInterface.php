<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 授权检查器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthorizationCheckerInterface
{
    /**
     * 检查给定的Token是否被授权执行某个操作或访问某个资源
     *
     * @param TokenInterface|null $token 认证Token对象
     * @param string|string[] $attribute 要检查的权限、角色或属性
     * @param mixed $subject 要检查权限的对象
     * @return bool 如果被授权则返回true，否则返回false
     */
    public function check(?TokenInterface $token, string|array $attribute, mixed $subject): bool;
}
