<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Psr\Http\Message\ServerRequestInterface;
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
        private array $options,
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        if (!$event->getRequest()->isMethod('POST')) {
            return;
        }

        $accessToken = $this->extractAccessToken($event->getRequest());

        $this->opaqueTokenIssuer->revoke($accessToken);
    }

    /**
     * 提取AccessToken
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function extractAccessToken(ServerRequestInterface $request): ?string
    {
        if (!$request->hasHeader($this->options['header_param']) || !\is_string($header = $request->getHeaderLine($this->options['header_param']))) {
            return null;
        }

        $regex = \sprintf(
            '/^%s([a-zA-Z0-9\-_\+~\/\.]+=*)$/',
            '' === $this->options['token_type'] ? '' : preg_quote($this->options['token_type']) . '\s+'
        );

        if (preg_match($regex, $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
