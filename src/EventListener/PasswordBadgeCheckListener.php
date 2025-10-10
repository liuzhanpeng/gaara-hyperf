<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\InvalidPasswordException;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherInterface;
use Lzpeng\HyperfAuthGuard\User\PasswordAwareUserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 检查密码凭证监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordBadgeCheckListener implements EventSubscriberInterface
{
    /**
     * @param PasswordHasherInterface $passwordHasher
     */
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            CheckPassportEvent::class => 'checkPassport'
        ];
    }

    /**
     * @inheritDoc
     */
    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        /**
         * @var PasswordBadge|null $badge
         */
        $badge = $passport->getBadge(PasswordBadge::class);
        if (is_null($badge) || $badge->isResolved()) {
            return;
        }

        $user = $passport->getUser();
        if (!$user instanceof PasswordAwareUserInterface) {
            throw new \LogicException('The user must implement PasswordAwareUserInterface');
        }

        if (!$this->passwordHasher->verify($badge->getPassword(), $user->getPassword())) {
            throw new InvalidPasswordException('密码错误', $user->getIdentifier());
        }

        $badge->resolve();
    }
}
