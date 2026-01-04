<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * 无效access token异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class InvalidAccessTokenException extends InvalidCredentialsException
{
    public function __construct(
        string $message,
        private string $accessToken,
        string $userIdentifier = ''
    ) {
        return parent::__construct($message, $userIdentifier);
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }
}
