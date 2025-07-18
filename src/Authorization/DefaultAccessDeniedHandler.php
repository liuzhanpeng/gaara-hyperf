<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AccessDeniedException;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 默认的访问控制拒绝处理器
 * 
 * 直接抛出 AccessDeniedException 异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class DefaultAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request, ?TokenInterface $token, string|array $attribute, mixed $subject = null): ResponseInterface
    {
        throw AccessDeniedException::from($token, $attribute, $subject);
    }
}
