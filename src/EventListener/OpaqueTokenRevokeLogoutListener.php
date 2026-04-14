<?php

declare(strict_types=1);

namespace GaaraHyperf\EventListener;

use GaaraHyperf\Event\LogoutEvent;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenProcessorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 撤消OpaqueToken登出监听器.
 */
class OpaqueTokenRevokeLogoutListener implements EventSubscriberInterface
{
    public function __construct(
        private OpaqueTokenProcessorInterface $opaqueTokenProcessor,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            LogoutEvent::class => ['onLogout', Priority::NORMAL],
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        if ($event->getRequest()->getMethod() !== 'POST') {
            return;
        }

        $accessToken = $this->opaqueTokenProcessor->extract($event->getRequest());
        if (is_null($accessToken)) {
            return;
        }

        $this->opaqueTokenProcessor->revoke($accessToken);
    }
}
