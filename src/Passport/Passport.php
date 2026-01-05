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
     * 用户加载器
     *
     * @var \Closure
     */
    private \Closure $userLoader;

    /**
     * @param string $userIdentifier 用户标识
     * @param callable $userLoader 用户加载器
     * @param BadgeInterface[] $badges 认证标识集合
     */
    public function __construct(
        private string $userIdentifier,
        callable $userLoader,
        private array $badges = [],
    ) {
        $this->userLoader = \Closure::fromCallable($userLoader);
        foreach ($badges as $badge) {
            $this->addBadge($badge);
        }
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
     * 返回所属用户
     *
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        if (is_null($this->user)) {
            $user = ($this->userLoader)($this->userIdentifier);
            if (is_null($user)) {
                throw new UserNotFoundException(
                    message: 'User not found',
                    userIdentifier: $this->userIdentifier
                );
            }

            if (!$user instanceof UserInterface) {
                throw new \LogicException(sprintf('The user provider must return a UserInterface object, %s given', get_debug_type($user)));
            }

            $this->user = $user;
        }

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
}
