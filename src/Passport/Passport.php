<?php

declare(strict_types=1);

namespace GaaraHyperf\Passport;

use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\User\UserInterface;

/**
 * 认证通行证
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class Passport
{
    /**
     * 用户
     *
     * @var UserInterface|null
     */
    private ?UserInterface $user = null;

    /**
     * 保存上下文信息的属性集合
     *
     * @var array
     */
    private array $attributes = [];

    /**
     * @param string $userIdentifier 用户标识
     * @param callable $userLoader 用户加载器
     * @param BadgeInterface[] $badges 认证标识集合
     */
    public function __construct(
        string $userIdentifier,
        callable $userLoader,
        private array $badges = [],
    ) {
        $userLoader = \Closure::fromCallable($userLoader);
        $user = ($userLoader)($userIdentifier);
        if (is_null($user)) {
            throw new UserNotFoundException(
                message: 'User not found',
                userIdentifier: $userIdentifier
            );
        }

        if (!$user instanceof UserInterface) {
            throw new \LogicException(sprintf('The user provider must return a UserInterface object, %s given', get_debug_type($user)));
        }

        $this->user = $user;
        foreach ($badges as $badge) {
            $this->addBadge($badge);
        }
    }

    /**
     * 返回所属用户
     *
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * 添加认证标识
     *
     * @param BadgeInterface $badge
     * @return void
     */
    public function addBadge(BadgeInterface $badge): void
    {
        $this->badges[$badge::class] = $badge;
    }

    /**
     * 返回指定认证标识
     *
     * @param string $name
     * @return BadgeInterface|null
     */
    public function getBadge(string $name): ?BadgeInterface
    {
        return $this->badges[$name] ?? null;
    }

    /**
     * 返回所有认认证标识
     *
     * @return array<string, BadgeInterface>
     */
    public function getBadges(): array
    {
        return $this->badges;
    }

    /**
     * 设置上下文信息属性
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
     * 获取上下文信息属性
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * 获取上下文信息属性集合
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
