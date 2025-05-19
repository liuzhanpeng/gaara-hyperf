<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Hyperf\Event\Contract\ListenerInterface;
use Lzpeng\HyperfAuthGuard\CsrfToken\CsrfToken;
use Lzpeng\HyperfAuthGuard\CsrfToken\CsrfTokenManagerInterface;
use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Passport\CsrfTokenBadge;

class CsrfTokenBadgeCheckListener implements ListenerInterface
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function listen(): array
    {
        return [
            CheckPassportEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$event instanceof CheckPassportEvent) {
            return;
        }

        /**
         * @var CsrfTokenBadge|null $badge
         */
        $badge = $event->getPassport()->getBadge(CsrfTokenBadge::class);
        if (!$badge || $badge->isResolved()) {
            return;
        }

        $csrfToken = new CsrfToken($badge->getId(), $badge->getToken());

        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new \LogicException('Invalid CSRF token.');
        }

        $badge->resolve();
    }
}
