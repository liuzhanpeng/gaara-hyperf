<?php

declare(strict_types=1);

namespace GaaraHyperf\Token;

/**
 * 已认证成功令牌
 * 
 * 只有持有这个令牌才表示最终认证成功
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatedToken implements TokenInterface
{
    /**
     * @param string $guardName
     */
    public function __construct(
        protected string $guardName,
        protected string $userIdentifier,
        protected array $attributes = []
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
     * 返回用户标识
     *
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
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
     * @inheritDoc
     */
    public function __toString(): string
    {
        return sprintf(
            '%s(user=%s, attributes=%s)',
            static::class,
            $this->userIdentifier,
            json_encode($this->attributes)
        );
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'guard_name' => $this->guardName,
            'user_identifier' => $this->userIdentifier,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->guardName = $data['guard_name'];
        $this->userIdentifier = $data['user_identifier'];
        $this->attributes = $data['attributes'];
    }
}
