<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * 无效access token异常.
 */
class InvalidAccessTokenException extends InvalidCredentialsException
{
    public function __construct(
        string $message,
        private string $accessToken,
        string $userIdentifier = ''
    ) {
        parent::__construct($message, $userIdentifier);
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }
}
