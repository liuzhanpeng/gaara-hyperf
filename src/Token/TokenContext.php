<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Token;

/**
 * 内置的用户令牌上下文
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class TokenContext implements TokenContextInterface
{
    /**
     * 用户令牌
     *
     * @var TokenInterface|null
     */
    private ?TokenInterface $token = null;

    /**
     * @inheritDoc
     */
    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    /**
     * @inheritDoc
     */
    public function setToken(?TokenInterface $token): void
    {
        $this->token = $token;
    }
}
