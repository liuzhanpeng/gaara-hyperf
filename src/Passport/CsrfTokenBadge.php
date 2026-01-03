<?php

declare(strict_types=1);

namespace GaaraHyperf\Passport;

/**
 * CSRF令牌认证标识
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CsrfTokenBadge implements BadgeInterface
{
    /**
     * 是否已被解决
     *
     * @var boolean
     */
    private bool $isResolved = false;

    /**
     * @param string $id
     * @param string $token
     */
    public function __construct(
        private string $id,
        #[\SensitiveParameter]
        private string $token
    ) {
        $this->token = $token;
    }

    /**
     * 返回令牌ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 返回CSRF令牌
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * 是否已解决
     *
     * @return boolean
     */
    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    /**
     * 设为已解决
     *
     * @return void
     */
    public function resolve(): void
    {
        $this->isResolved = true;
    }
}
