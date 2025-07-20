<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * IP白名单检查监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class IPWhiteListListener implements EventSubscriberInterface
{
    public function __construct(
        private array $ipWhiteList = []
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
        $ip = $this->getIp($event->getRequest());
        if (empty($this->ipWhiteList) || in_array($ip, $this->ipWhiteList, true)) {
            return;
        }

        throw new AuthenticationException('IP not in whitelist', $passport->getUser()->getIdentifier());
    }

    /**
     * 获取请求的IP地址，支持代理转发
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getIp(ServerRequestInterface $request): string
    {
        // 优先取 X-Forwarded-For，可能有多个IP，取第一个
        $forwardedFor = $request->getHeaderLine('x-forwarded-for');
        if (!empty($forwardedFor)) {
            $ips = array_map('trim', explode(',', $forwardedFor));
            if (!empty($ips[0])) {
                return $ips[0];
            }
        }

        // 兼容 X-Real-IP
        $realIp = $request->getHeaderLine('x-real-ip');
        if (!empty($realIp)) {
            return $realIp;
        }

        // 兼容 Hyperf 的 ip 属性
        $ip = $request->getAttribute('ip');
        if (!empty($ip)) {
            return $ip;
        }

        // 最后取 server param
        return $request->getServerParams()['remote_addr'] ?? '';
    }
}
