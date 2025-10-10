<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\LogoutEvent;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 撤消OpaqueToken登出监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenRevokeLogoutListener implements EventSubscriberInterface
{
    public function __construct(
        private OpaqueTokenIssuerInterface $opaqueTokenIssuer,
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        if ($event->getRequest()->getMethod() !== 'POST') {
            return;
        }

        $accessToken = $this->opaqueTokenIssuer->extractAccessToken($event->getRequest());
        if (is_null($accessToken)) {
            return;
        }

        $this->opaqueTokenIssuer->revoke($accessToken);
    }
}
