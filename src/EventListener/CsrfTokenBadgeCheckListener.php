<?php

declare(strict_types=1);

namespace GaaraHyperf\EventListener;

use GaaraHyperf\CsrfTokenManager\CsrfToken;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\Exception\InvalidCsrfTokenException;
use GaaraHyperf\Passport\CsrfTokenBadge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * CSRF令牌检查监听器.
 */
class CsrfTokenBadgeCheckListener implements EventSubscriberInterface
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            CheckPassportEvent::class => ['checkPassport', 100],
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        /**
         * @var null|CsrfTokenBadge $badge
         */
        $badge = $event->getPassport()->getBadge(CsrfTokenBadge::class);
        if (! $badge || $badge->isResolved()) {
            return;
        }

        $csrfToken = new CsrfToken($badge->getId(), $badge->getToken());

        if (! $this->csrfTokenManager->verify($csrfToken)) {
            throw new InvalidCsrfTokenException(
                message: 'Invalid CSRF token',
                userIdentifier: $event->getPassport()->getUser()->getIdentifier(),
            );
        }

        $badge->resolve();
    }
}
