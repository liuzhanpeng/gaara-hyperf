<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\IPNotInWhiteListException;
use Lzpeng\HyperfAuthGuard\IPResolver\IPResolverInterface;
use Lzpeng\HyperfAuthGuard\IPWhiteListChecker\IPWhiteListChecker;
use Lzpeng\HyperfAuthGuard\IPWhiteListChecker\IPWhiteListProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * IP白名单检查监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class IPWhiteListListener implements EventSubscriberInterface
{
    public function __construct(
        private IPResolverInterface $ipResolver,
        private IPWhiteListChecker $whiteListChecker,
        private array|IPWhiteListProviderInterface $whiteList = []
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

        $whiteList = $this->whiteList instanceof IPWhiteListProviderInterface
            ? $this->whiteList->getWhiteList()
            : $this->whiteList;

        if (!$this->whiteListChecker->isAllowed($ip, $whiteList)) {
            throw new IPNotInWhiteListException($passport->getUserIdentifier(), $ip);
        }
    }
}
