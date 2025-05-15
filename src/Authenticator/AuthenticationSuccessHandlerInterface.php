<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证成功处理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticationSuccessHandlerInterface
{
    /**
     * @param RequestInterface $request
     * @param TokenInterface $token
     * @return ResponseInterface|null
     */
    public function handle(RequestInterface $request, TokenInterface  $token): ?ResponseInterface;
}
