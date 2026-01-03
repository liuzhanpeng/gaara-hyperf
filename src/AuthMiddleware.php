<?php

declare(strict_types=1);

namespace GaaraHyperf;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 认证中间件
 * 
 * 认证组件通过中间件介入请求处理流程，但没有在ConfigProvider中注册, 
 * 需要用户手动添加到中间件配置文件中(config/autoload/middlewares.php), 
 * 因为现实情况，可能只有后端需要认证，用户手动添加可以避免不必要的性能损耗
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
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->guardManager->process($request);
        if (!is_null($response)) {
            return $response;
        }

        return $handler->handle($request);
    }
}
