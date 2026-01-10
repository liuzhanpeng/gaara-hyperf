<?php

declare(strict_types=1);

namespace GaaraHyperf\User;

/**
 * 基于内存的用户
 * 
 * 用于测试或简单场景
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class MemoryUser implements UserInterface, PasswordAwareUserInterface, \Serializable
{
    /**
     * @param string $username
     * @param string $password
     */
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

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [$this->username, $this->password];
    }

    /**
     *
     * @param array $data
     * @return void
     */
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
