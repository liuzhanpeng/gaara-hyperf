<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Lzpeng\HyperfAuthGuard\Exception\AccessDeniedException;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 内置的访问控制拒绝处理器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private Util $util,
        private ?string $template = null
    ) {}

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request, AccessDeniedException $exception): ResponseInterface
    {
        if ($this->util->expectJson($request)) {
            return $this->response->json([
                'code' => 403,
                'message' => 'Access Denied',
            ]);
        } else {
            return $this->response->html(
                !is_null($this->template) ? $this->template : '<html><body>Access Denied</body></html>'
            );
        }
    }
}
