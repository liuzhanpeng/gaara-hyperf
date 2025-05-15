<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UnauthenticatedHandler;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Psr\Http\Message\ResponseInterface;

/**
 * 未认证处理器接口
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface UnauthenticatedHandlerInterface
{
    /**
     * 处理未认证异常
     *
     * @param RequestInterface $request
     * @param UnauthenticatedException $unauthenticatedException
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, UnauthenticatedException $unauthenticatedException): ResponseInterface;
}
