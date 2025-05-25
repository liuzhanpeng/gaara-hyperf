<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Logout;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Config\LogoutConfig;
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
        private LogoutConfig $config,
        private TokenStorageInterface $tokenStorage,
        private TokenContextInterface $tokenContext,
        private EventDispatcherInterface $eventDispatcher,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
    ) {}

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->config->path()
            && $request->getMethod() === 'POST';
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        $token = $this->tokenContext->getToken();
        if (is_null($token)) {
            throw AuthenticationException::from('未登录或会话已过期');
        }

        $logoutEvent = new LogoutEvent($token, $request);
        $this->eventDispatcher->dispatch($logoutEvent);

        $this->tokenStorage->delete($token->getGuardName());
        $this->tokenContext->setToken(null);

        $response = $logoutEvent->getResponse();
        if (!is_null($response)) {
            return $response;
        }

        if (!is_null($this->config->targetPath())) {
            return $this->response->redirect($this->config->targetPath());
        }

        return null;
    }
}
