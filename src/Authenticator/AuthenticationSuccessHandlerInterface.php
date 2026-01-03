<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

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
    public function handle(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface;
}
