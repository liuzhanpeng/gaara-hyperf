<?php

declare(strict_types=1);

namespace GaaraHyperf\UnauthenticatedHandler;

use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 未认证请求处理器接口.
 */
interface UnauthenticatedHandlerInterface
{
    /**
     * 处理未认证请求
     */
    public function handle(ServerRequestInterface $request, ?TokenInterface $token): ResponseInterface;
}
