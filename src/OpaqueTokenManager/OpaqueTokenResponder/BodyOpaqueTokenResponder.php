<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder;

use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class BodyOpaqueTokenResponder implements OpaqueTokenResponderInterface
{
    public function __construct(
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private ?string $template = null
    ) {
    }

    public function respond(OpaqueToken $opaqueToken): ResponseInterface
    {
        return $this->response->json(json_decode($this->getResponseTemplate($opaqueToken), true));
    }

    private function getResponseTemplate(OpaqueToken $opaqueToken): string
    {
        $template = str_replace(
            ['#ACCESS_TOKEN#', '#EXPIRES_IN#', '#USER_IDENTIFIER#'],
            [$opaqueToken->accessToken(), $opaqueToken->expiresIn(), $opaqueToken->token()->getUserIdentifier()],
            $this->template ?? '{"code": 0, "message": "success", "data": {"access_token": "#ACCESS_TOKEN#", "expires_in": #EXPIRES_IN#, "user_identifier": "#USER_IDENTIFIER#"}}'
        );

        if (! is_string($template) || ! is_array(json_decode($template, true))) {
            throw new InvalidArgumentException('Response template must be a valid JSON string');
        }

        return $template;
    }
}
