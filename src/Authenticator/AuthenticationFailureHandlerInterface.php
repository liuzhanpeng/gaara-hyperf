<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证失败处理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticationFailureHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param AuthenticationException $exception
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, AuthenticationException  $exception): ResponseInterface;
}
