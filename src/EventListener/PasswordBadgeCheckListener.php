<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Hyperf\Event\Contract\ListenerInterface;
use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\InvalidPasswordException;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolverInterface;
use Lzpeng\HyperfAuthGuard\User\PasswordUserInterface;

/**
 * 检查密码凭证监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordBadgeCheckListener implements ListenerInterface
{
    /**
     * @param PasswordHasherResolverInterface $passwordHasherResolver
     */
    public function __construct(
        private PasswordHasherResolverInterface $passwordHasherResolver
    ) {}

    /**
     * @inheritDoc
     */
    public function listen(): array
    {
        return [
            CheckPassportEvent::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(object $event): void
    {
        if (!$event instanceof CheckPassportEvent) {
            return;
        }

        $passport = $event->getPassport();

        /**
         * @var PasswordBadge|null $badge
         */
        $badge = $passport->getBadge(PasswordBadge::class);
        if (is_null($badge) || $badge->isResolved()) {
            return;
        }

        $user = $passport->getUser();
        if (!$user instanceof PasswordUserInterface) {
            throw new \LogicException('The user must implement PasswordUserInterface');
        }

        $passwordHasher = $this->passwordHasherResolver->resolve($passport->getGuardName());
        if (!$passwordHasher->verify($badge->getPassword(), $user->getPassword())) {
            throw InvalidPasswordException::from('密码错误', $user);
        }

        $badge->resolve();
    }
}
