<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Token;

use Lzpeng\HyperfAuthGuard\User\UserInterface;

/**
 * 抽象令牌
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
abstract class AbstractToken implements TokenInterface, \Serializable
{
    /**
     * @param string $guardName
     */
    public function __construct(
        private string $guardName,
        private UserInterface $user,
        private array $attributes = []
    ) {}

    /**
     * 返回认证守卫名称
     *
     * @return string
     */
    public function getGuardName(): string
    {
        return $this->guardName;
    }

    /**
     * 返回用户
     *
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * 是否存在属性
     *
     * @param string $name
     * @return boolean
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * 返回指定属性
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name): mixed
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('This token has no "%s" attribute.', $name));
        }

        return $this->attributes[$name];
    }

    /**
     * 设置属性
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * 序列化
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [$this->guardName, $this->user, $this->attributes];
    }

    /**
     * 反序列化
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        [$this->guardName, $this->user, $this->attributes] = $data;
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

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return sprintf(
            '%s(user=%s, attributes=%s)',
            static::class,
            $this->getUser()->getIdentifier(),
            json_encode($this->attributes)
        );
    }
}
