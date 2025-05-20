<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 认证中间件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @param GuardManager $guardManager
     */
    public function __construct(
        private GuardManager $guardManager,
        private RequestInterface $request,
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->guardManager->process($this->request);
        if (!is_null($response)) {
            return $response;
        }

        return $handler->handle($request);
    }
}
