<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\User;

class MemoryUser implements UserInterface, PasswordUserInterface
{
    public function __construct(
        private string $username,
        private string $password,
    ) {}

    /**
     * 返回用户名
     *
     * @return string
     */
    public function username(): string
    {
        return $this->username;
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
