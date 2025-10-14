<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\IPNotInWhiteListException;
use Lzpeng\HyperfAuthGuard\Utils\IpResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * IP白名单检查监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class IPWhiteListListener implements EventSubscriberInterface
{
    public function __construct(
        private IpResolver $ipResolver,
        private array $whiteList = []
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => 'checkPassport',
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $ip = $this->ipResolver->resolve($event->getRequest());
        if (empty($this->whiteList) || in_array($ip, $this->whiteList, true)) {
            return;
        }

        throw new IPNotInWhiteListException('IP not in whitelist', $passport->getUser()->getIdentifier());
    }
}
