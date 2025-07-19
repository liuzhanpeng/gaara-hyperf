<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

/**
 * 无效的CSRF令牌异常
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class InvalidCsrfTokenException extends AuthenticationException
{
    public function getDisplayMessage(): string
    {
        return '无效的CSRF令牌';
    }
}
