<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AccessDeniedException;
use Psr\Http\Message\ResponseInterface;

/**
 * 访问控制拒绝处理器接口
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AccessDeniedHandlerInterface
{
    /**
     * 处理访问控制拒绝
     *
     * @param RequestInterface $request
     * @param AccessDeniedException $accessDeniedException
     * @return ResponseInterface|null
     */
    public function handle(RequestInterface $request, AccessDeniedException $accessDeniedException): ?ResponseInterface;
}
