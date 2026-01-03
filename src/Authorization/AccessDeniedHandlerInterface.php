<?php

declare(strict_types=1);

namespace GaaraHyperf\Authorization;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Token\TokenInterface;
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
     * @param ServerRequestInterface $request
     * @param TokenInterface|null $token
     * @param string|array $attribute
     * @param mixed $subject
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, ?TokenInterface $token, string|array $attribute, mixed $subject = null): ResponseInterface;
}
