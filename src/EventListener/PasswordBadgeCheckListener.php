<?php

declare(strict_types=1);

namespace GaaraHyperf\EventListener;

use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\Exception\InvalidPasswordException;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\PasswordHasher\PasswordHasherInterface;
use GaaraHyperf\User\PasswordAwareUserInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 检查密码凭证监听器.
 */
class PasswordBadgeCheckListener implements EventSubscriberInterface
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            CheckPassportEvent::class => ['checkPassport', Priority::NORMAL],
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        /**
         * @var null|PasswordBadge $badge
         */
        $badge = $passport->getBadge(PasswordBadge::class);
        if (is_null($badge) || $badge->isResolved()) {
            return;
        }

        $user = $passport->getUser();
        if (! $user instanceof PasswordAwareUserInterface) {
            throw new RuntimeException('The user must implement PasswordAwareUserInterface');
        }

        if (! $this->passwordHasher->verify($badge->getPassword(), $user->getPassword())) {
            throw new InvalidPasswordException(
                message: 'Invalid password',
                userIdentifier: $user->getIdentifier()
            );
        }

        $badge->resolve();
    }
}
