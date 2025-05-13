<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UnauthenticatedHandler;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Psr\Http\Message\ResponseInterface;

class DefaultUnauthenticatedStrategy implements UnauthenticatedStrategyInterface
{
    public function __construct(
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private Util $util,
        private string $targetUrl,
    ) {}

    public function supports(ServerRequestInterface $request, UnauthenticatedException $unauthenticatedException): bool
    {
        return true;
    }

    public function handle(ServerRequestInterface $request, UnauthenticatedException $unauthenticatedException): ResponseInterface
    {
        if ($this->util->expectJson($request)) {
            return $this->response->json([
                'code' => 401,
                'message' => '未认证'
            ], 401);
        } else {
            $redirectUrl  = (string) $request->getUri();

            return $this->response->redirect(sprintf(
                '%s?redirect_url=%s',
                $this->targetUrl,
                urlencode($redirectUrl)
            ));
        }
    }
}
