<?php

declare(strict_types=1);

namespace GaaraHyperf\Token;

use InvalidArgumentException;

/**
 * 已认证成功令牌.
 *
 * 只有持有这个令牌才表示最终认证成功
 */
class AuthenticatedToken implements TokenInterface
{
    public function __construct(
        protected string $guardName,
        protected string $userIdentifier,
        protected array $attributes = []
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s(user=%s, attributes=%s)',
            static::class,
            $this->userIdentifier,
            json_encode($this->attributes)
        );
    }

    public function __serialize(): array
    {
        return [
            'guard_name' => $this->guardName,
            'user_identifier' => $this->userIdentifier,
            'attributes' => $this->attributes,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->guardName = $data['guard_name'];
        $this->userIdentifier = $data['user_identifier'];
        $this->attributes = $data['attributes'];
    }

    /**
     * 返回认证守卫名称.
     */
    public function getGuardName(): string
    {
        return $this->guardName;
    }

    /**
     * 返回用户标识.
     */
    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    /**
     * 是否存在属性.
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * 返回指定属性.
     */
    public function getAttribute(string $name): mixed
    {
        if (! array_key_exists($name, $this->attributes)) {
            throw new InvalidArgumentException(sprintf('This token has no "%s" attribute.', $name));
        }

        return $this->attributes[$name];
    }

    /**
     * 设置属性.
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }
}
