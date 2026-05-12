<?php

declare(strict_types=1);

namespace GaaraHyperf\Authorization;

use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 访问控制拒绝处理器接口.
 */
interface AccessDeniedHandlerInterface
{
    /**
     * 处理访问控制拒绝.
     */
    public function handle(ServerRequestInterface $request, ?TokenInterface $token, mixed $object, mixed $action = null): ResponseInterface;
}
