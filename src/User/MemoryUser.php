<?php

declare(strict_types=1);

namespace GaaraHyperf\User;

use BadMethodCallException;
use SensitiveParameter;
use Serializable;

/**
 * 基于内存的用户.
 *
 * 用于测试或简单场景
 */
class MemoryUser implements UserInterface, PasswordAwareUserInterface, Serializable
{
    public function __construct(
        private string $username,
        #[SensitiveParameter]
        private string $password,
    ) {
    }

    public function __serialize(): array
    {
        return [$this->username, $this->password];
    }

    public function __unserialize(array $data): void
    {
        [$this->username, $this->password] = $data;
    }

    /**
     * 返回用户名.
     */
    public function username(): string
    {
        return $this->username;
    }

    public function getIdentifier(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @internal
     */
    final public function serialize(): string
    {
        throw new BadMethodCallException('Cannot serialize ' . __CLASS__);
    }

    final public function unserialize(string $serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }
}
