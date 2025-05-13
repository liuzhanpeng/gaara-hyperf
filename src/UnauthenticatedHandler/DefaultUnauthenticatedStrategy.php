<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UnauthenticatedHandler;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Psr\Http\Message\ResponseInterface;

class DefaultUnauthenticatedStrategy implements UnauthenticatedStrategyInterface
{
    public function __construct(
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private string $targetUrl,
    ) {}

    public function supports(ServerRequestInterface $request, UnauthenticatedException $unauthenticatedException): bool
    {
        return true;
    }

    public function handle(ServerRequestInterface $request, UnauthenticatedException $unauthenticatedException): ResponseInterface
    {
        if ($this->expectJson($request)) {
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

    /**
     * @param ServerRequestInterface $request
     * @return boolean
     */
    private function expectJson(ServerRequestInterface $request): bool
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        if (empty($acceptHeader) || $acceptHeader === '*/*') {
            return false;
        }

        $parts = explode(',', $acceptHeader);
        foreach ($parts as $part) {
            $part = trim($part);
            $part = explode(';', $part, 2)[0];
            $part = trim($part);
            if (stripos($part, 'application/json') === 0) {
                return true;
            }
        }

        return false;
    }
}
