<?php

declare(strict_types=1);

namespace GaaraHyperf\Token;

/**
 * 用户令牌上下文接口.
 */
interface TokenContextInterface
{
    /**
     * 获取令牌.
     */
    public function getToken(): ?TokenInterface;

    /**
     * 设置令牌.
     */
    public function setToken(?TokenInterface $token): void;
}
