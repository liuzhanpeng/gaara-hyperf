<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UnauthenticatedHandler;

use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 未认证处理策略接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface UnauthenticatedStrategyInterface
{
    /**
     * 是否支持
     *
     * @param ServerRequestInterface $request
     * @param UnauthenticatedException $unauthenticatedException
     * @return boolean
     */
    public function supports(ServerRequestInterface $request, UnauthenticatedException $unauthenticatedException): bool;

    /**
     * 处理
     *
     * @param ServerRequestInterface $request
     * @param UnauthenticatedException $unauthenticatedException
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, UnauthenticatedException $unauthenticatedException): ResponseInterface;
}
