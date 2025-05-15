<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Lzpeng\HyperfAuthGuard\Exception\AccessDeniedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 内置的访问控制拒绝处理器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct() {}

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request, AccessDeniedException $exception): ResponseInterface
    {
        throw $exception;
    }
}
