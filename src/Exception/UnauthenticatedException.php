<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

use GaaraHyperf\Token\TokenInterface;

/**
 * 未认证异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UnauthenticatedException extends \Exception
{
    public function __construct(
        string $message = 'Unauthenticated',
        private ?TokenInterface $token = null
    ) {
        parent::__construct($message);
        $this->token = $token;
    }

    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }
}
