<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use TheSeer\Tokenizer\Token;

/**
 * 未认证异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UnauthenticatedException extends \RuntimeException
{
    private ?TokenInterface $token;

    public function __construct(?TokenInterface $token = null)
    {
        parent::__construct('Unauthorized', 401);
        $this->token = $token;
    }

    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    public function getDisplayMessage(): string
    {
        return '未认证或会话已过期';
    }
}
