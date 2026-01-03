<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证失败处理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticationFailureHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param AuthenticationException $exception
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, AuthenticationException  $exception): ResponseInterface;
}
