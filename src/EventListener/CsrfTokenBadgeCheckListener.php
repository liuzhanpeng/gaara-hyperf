<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\CsrfTokenManager\CsrfToken;
use Lzpeng\HyperfAuthGuard\CsrfTokenManager\CsrfTokenManagerInterface;
use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\InvalidCsrfTokenException;
use Lzpeng\HyperfAuthGuard\Passport\CsrfTokenBadge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * CSRF令牌检查监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CsrfTokenBadgeCheckListener implements EventSubscriberInterface
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            CheckPassportEvent::class => 'checkPassport',
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        /**
         * @var CsrfTokenBadge|null $badge
         */
        $badge = $event->getPassport()->getBadge(CsrfTokenBadge::class);
        if (!$badge || $badge->isResolved()) {
            return;
        }

        $csrfToken = new CsrfToken($badge->getId(), $badge->getToken());

        if (!$this->csrfTokenManager->verify($csrfToken)) {
            throw new InvalidCsrfTokenException('CSRF token is invalid or expired');
        }

        $badge->resolve();
    }
}
