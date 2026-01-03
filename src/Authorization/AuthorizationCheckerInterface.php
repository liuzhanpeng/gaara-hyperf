<?php

declare(strict_types=1);

namespace GaaraHyperf\Authorization;

use GaaraHyperf\Token\TokenInterface;

/**
 * 授权检查器接口
 * 
 * 本组件不提供授权功能, 用户通过实现该接口接入自己的授权系统
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthorizationCheckerInterface
{
    /**
     * 检查给定的Token是否被授权执行某个操作或访问某个资源
     *
     * @param TokenInterface $token 认证Token对象
     * @param string|string[] $attribute 要检查的权限、角色或属性
     * @param mixed $subject 要检查权限的对象，可选
     * @return bool
     */
    public function check(TokenInterface $token, string|array $attribute, mixed $subject = null): bool;
}
