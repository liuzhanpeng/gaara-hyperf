<?php

declare(strict_types=1);

namespace GaaraHyperf\Authorization;

use GaaraHyperf\Exception\AccessDeniedException;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 默认的访问控制拒绝处理器.
 *
 * 直接抛出 AccessDeniedException 异常
 */
class DefaultAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(ServerRequestInterface $request, ?TokenInterface $token, mixed $object, mixed $action = null): ResponseInterface
    {
        throw new AccessDeniedException(
            token: $token,
            object: $object,
            action: $action
        );
    }
}
