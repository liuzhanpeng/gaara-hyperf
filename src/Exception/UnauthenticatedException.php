<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 未认证异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UnauthenticatedException extends \Exception
{
    public function __construct(private ?TokenInterface $token = null)
    {
        parent::__construct();
        $this->token = $token;
    }

    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }
}
