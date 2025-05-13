<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Lzpeng\HyperfAuthGuard\Exception\AccessDeniedException;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @param ServerRequestInterface $request
     * @param AccessDeniedException $accessDeniedException
     * @return ResponseInterface|null
     */
    public function handle(ServerRequestInterface $request, AccessDeniedException $accessDeniedException): ?ResponseInterface;
}
