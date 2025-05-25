<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guard 管理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface GuardManagerInterface
{
    /**
     * 处理请求
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function process(ServerRequestInterface $request): ?ResponseInterface;
}
