<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Logout;

use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 登出处理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface LogoutHandlerInterface
{
    /**
     * 是否支持当前请求
     * 
     * @param RequestInterface $request
     * @return boolean
     */
    public function supports(RequestInterface $request): bool;

    /**
     * 处理登出请求
     *
     * @param RequestInterface $request
     * @return ResponseInterface|null
     */
    public function handle(RequestInterface $request): ?ResponseInterface;
}
