<?php

declare(strict_types=1);

namespace GaaraHyperf\UnauthenticatedHandler;

use GaaraHyperf\Exception\UnauthenticatedException;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 默认未认证处理器
 * 
 * 直接抛出 UnauthenticatedException 异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class DefaultUnauthenticatedHandler implements UnauthenticatedHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request, ?TokenInterface $token): ResponseInterface
    {
        throw new UnauthenticatedException($token);
    }
}
