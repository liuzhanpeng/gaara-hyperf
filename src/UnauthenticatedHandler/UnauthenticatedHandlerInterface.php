<?php

declare(strict_types=1);

namespace GaaraHyperf\UnauthenticatedHandler;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 未认证请求处理器接口
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface UnauthenticatedHandlerInterface
{
    /**
     * 处理未认证请求
     *
     * @param ServerRequestInterface $request
     * @param TokenInterface|null $token
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, ?TokenInterface $token): ResponseInterface;
}
