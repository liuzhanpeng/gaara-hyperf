<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证成功处理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticationSuccessHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param TokenInterface $token
     * @return ResponseInterface|null
     */
    public function handle(ServerRequestInterface $request, TokenInterface  $token): ?ResponseInterface;
}
