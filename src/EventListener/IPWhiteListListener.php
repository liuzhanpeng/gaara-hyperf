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
        private array|string|IPWhiteListProviderInterface $whiteList = []
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

        $whiteList = $this->resolveWhiteList();

        if (!$this->whiteListChecker->isAllowed($ip, $whiteList)) {
            throw new IPNotInWhiteListException($passport->getUserIdentifier(), $ip);
        }
    }

    /**
     * 解析白名单
     *
     * @return array
     */
    private function resolveWhiteList(): array
    {
        if ($this->whiteList instanceof IPWhiteListProviderInterface) {
            return $this->whiteList->getWhiteList();
        }

        if (is_string($this->whiteList)) {
            if (!class_exists($this->whiteList)) {
                throw new \InvalidArgumentException(sprintf('White list provider class "%s" does not exist.', $this->whiteList));
            }

            $provider = new $this->whiteList();

            if (!$provider instanceof IPWhiteListProviderInterface) {
                throw new \InvalidArgumentException(sprintf('White list provider class "%s" must implement IPWhiteListProviderInterface.', $this->whiteList));
            }

            return $provider->getWhiteList();
        }

        return (array) $this->whiteList;
    }
}
