<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Logout;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Event\LogoutEvent;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageInterface;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 内置的登出处理器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LogoutHandler implements LogoutHandlerInterface
{
    public function __construct(
        private string $path,
        private string $target,
        private TokenStorageInterface $tokenStorage,
        private TokenContextInterface $tokenContext,
        private EventDispatcherInterface $eventDispatcher,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private Util $util,
    ) {}

    /**
     * @inheritDoc
     */
    public function supports(RequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->path && $request->getMethod() === 'POST';
    }

    /**
     * @inheritDoc
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        $token = $this->tokenContext->getToken();
        if (is_null($token)) {
            throw AuthenticationException::from('未登录或会话已过期');
        }

        $logoutEvent = new LogoutEvent($token->getGuardName(), $token, $request);
        $this->eventDispatcher->dispatch($logoutEvent);

        $this->tokenStorage->delete($token->getGuardName());
        $this->tokenContext->setToken(null);

        $response = $logoutEvent->getResponse();
        if (!is_null($response)) {
            return $response;
        }

        return $this->createDefaultResponse($request);
    }

    /**
     * 创建默认的登出响应
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    private function createDefaultResponse(RequestInterface $request): ResponseInterface
    {
        if ($this->util->expectJson($request)) {
            return $this->response->json([
                'code' => 0,
                'msg' => '登出成功'
            ]);
        } else {
            return $this->response->redirect($this->target ?? '/');
        }
    }
}
