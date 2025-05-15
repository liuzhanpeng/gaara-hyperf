<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证失败处理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticationFailureHandlerInterface
{
    /**
     * @param RequestInterface $request
     * @param AuthenticationException $exception
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, AuthenticationException  $exception): ResponseInterface;
}
