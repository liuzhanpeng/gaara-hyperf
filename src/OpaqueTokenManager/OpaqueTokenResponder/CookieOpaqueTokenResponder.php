<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder;

use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use Hyperf\HttpMessage\Cookie\Cookie;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class CookieOpaqueTokenResponder implements OpaqueTokenResponderInterface
{
    public function __construct(
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private string $cookieName = 'access_token',
        private string $cookiePath = '/',
        private string $cookieDomain = '',
        private bool $cookieSecure = true,
        private bool $cookieHttpOnly = true,
        private string $cookieSameSite = 'lax',
        private ?string $template = null,
    ) {
    }

    public function respond(OpaqueToken $opaqueToken): ResponseInterface
    {
        $cookie = new Cookie(
            name: $this->cookieName,
            value: $opaqueToken->accessToken(),
            expire: time() + $opaqueToken->expiresIn(),
            path: $this->cookiePath,
            domain: $this->cookieDomain,
            secure: $this->cookieSecure,
            httpOnly: $this->cookieHttpOnly,
            sameSite: $this->cookieSameSite,
        );

        return $this->response->withCookie($cookie)->json(json_decode($this->getResponseTemplate($opaqueToken), true));
    }

    private function getResponseTemplate(OpaqueToken $opaqueToken): string
    {
        $template = str_replace(
            ['#ACCESS_TOKEN#', '#EXPIRES_IN#', '#USER_IDENTIFIER#'],
            [$opaqueToken->accessToken(), $opaqueToken->expiresIn(), $opaqueToken->token()->getUserIdentifier()],
            $this->template ?? '{"code": 0, "message": "success"}'
        );

        if (! is_string($template) || ! is_array(json_decode($template, true))) {
            throw new InvalidArgumentException('Response template must be a valid JSON string');
        }

        return $template;
    }
}
