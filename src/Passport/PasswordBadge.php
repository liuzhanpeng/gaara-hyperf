<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Passport;


/**
 * 密码凭证标识
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordBadge implements BadgeInterface
{
    /**
     * 是否已被解决
     *
     * @var boolean
     */
    private bool $isResolved = false;

    /**
     * @param string $password
     */
    public function __construct(
        #[\SensitiveParameter]
        private string $password
    ) {}

    /**
     * 返回密码
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
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

    /**
     * @inheritDoc
     */
    public function isResolved(): bool
    {
        return $this->isResolved;
    }
}
