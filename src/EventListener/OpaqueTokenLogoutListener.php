<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Event\LogoutEvent;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerInterface;

class OpaqueTokenLogoutListener implements ListenerInterface
{
    public function __construct(
        private OpaqueTokenIssuerInterface $opaqueTokenIssuer,
        private array $options,
    ) {}

    public function listen(): array
    {
        return [
            LogoutEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$event instanceof LogoutEvent) {
            return;
        }

        if (!$event->getRequest()->isMethod('POST')) {
            return;
        }

        $accessToken = $this->extractAccessToken($event->getRequest());

        $this->opaqueTokenIssuer->revoke($accessToken);
    }

    /**
     * 提取AccessToken
     *
     * @param RequestInterface $request
     * @return string|null
     */
    public function extractAccessToken(RequestInterface $request): ?string
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
