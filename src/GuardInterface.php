<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证守卫接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface GuardInterface
{
    /**
     * 处理认证请求
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function authenticate(ServerRequestInterface $request): ?ResponseInterface;
}
