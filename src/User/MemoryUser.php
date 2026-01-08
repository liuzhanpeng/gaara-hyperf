<?php

declare(strict_types=1);

namespace GaaraHyperf\User;

class MemoryUser implements UserInterface, PasswordAwareUserInterface, \Serializable
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

    public function __serialize(): array
    {
        return [$this->username, $this->password];
    }

    public function __unserialize(array $data): void
    {
        [$this->username, $this->password] = $data;
    }

    /**
     * @internal
     */
    final public function serialize(): string
    {
        throw new \BadMethodCallException('Cannot serialize ' . __CLASS__);
    }

    /**
     * @inheritDoc
     */
    final public function unserialize(string $serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }
}
