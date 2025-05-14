<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Logout;

use Lzpeng\HyperfAuthGuard\Config\LogoutConfig;
use Lzpeng\HyperfAuthGuard\Event\LogoutEvent;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageResolverInterface;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 内置的登出处理器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LogoutHandler implements LogoutHandlerInterface
{
    public function __construct(
        private LogoutConfig $config,
        private TokenStorageResolverInterface $tokenStorageResolver,
        private TokenContextInterface $tokenContext,
        private EventDispatcherInterface $eventDispatcher,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private Util $util,
    ) {}

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->config->path() && $request->getMethod() === 'POST';
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->tokenContext->getToken();
        if (is_null($token)) {
            return $this->createDefaultResponse($request);
        }

        $logoutEvent = new LogoutEvent($token, $request);
        $this->eventDispatcher->dispatch($logoutEvent);

        $response = $logoutEvent->getResponse();
        if (!is_null($response)) {
            return $response;
        }

        $tokenStorage = $this->tokenStorageResolver->resolve($token->getGuardName());
        $tokenStorage->delete($token->getGuardName());

        $this->tokenContext->setToken(null);

        return $this->createDefaultResponse($request);
    }

    /**
     * 创建默认的登出响应
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    private function createDefaultResponse(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->util->expectJson($request)) {
            return $this->response->json([
                'message' => 'Logout failed',
            ]);
        } else {
            return $this->response->redirect($this->config->path() ?? '/');
        }
    }
}
