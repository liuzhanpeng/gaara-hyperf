<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Token;

/**
 * 用户令牌上下文接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface TokenContextInterface
{
    /**
     * 获取令牌
     *
     * @return TokenInterface|null
     */
    public function getToken(): ?TokenInterface;

    /**
     * 设置令牌
     *
     * @param TokenInterface $token|null
     * @return void
     */
    public function setToken(?TokenInterface $token): void;
}
