<?php

declare(strict_types=1);

namespace GaaraHyperf\Authorization;

use GaaraHyperf\Token\TokenInterface;

/**
 * 授权检查器接口.
 *
 * 本组件不提供授权功能, 用户通过实现该接口接入自己的授权系统
 */
interface AuthorizationCheckerInterface
{
    /**
     * 检查给定的Token是否被授权执行某个操作或访问某个资源.
     *
     * @param TokenInterface $token 认证Token对象
     * @param mixed $object 要检查权限的资源
     * @param mixed $action 要检查的操作或权限
     */
    public function check(TokenInterface $token, mixed $object, mixed $action = null): bool;
}
