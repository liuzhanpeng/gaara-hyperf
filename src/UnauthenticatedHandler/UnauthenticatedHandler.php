<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UnauthenticatedHandler;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Psr\Http\Message\ResponseInterface;

/**
 * 内置的未认证处理器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UnauthenticatedHandler implements UnauthenticatedHandlerInterface
{
    public function __construct() {}

    /**
     * @inheritDoc
     */
    public function handle(RequestInterface $request, UnauthenticatedException $unauthenticatedException): ResponseInterface
    {
        throw $unauthenticatedException;
    }
}
