<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Logout;

use Lzpeng\HyperfAuthGuard\Event\LogoutEvent;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenStorageInterface;
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
        private array $config,
        private TokenStorageInterface $tokenStorage,
        private TokenContextInterface $tokenContext,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        $this->config = array_merge([
            'path' => '/logout',
            'target' => '/login'
        ], $config);
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->config['path'];
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->tokenContext->getToken();

        $logoutEvent = new LogoutEvent($token, $request);
        $this->eventDispatcher->dispatch($logoutEvent);

        $this->tokenStorage->delete($token->getGuardName());
        $this->tokenContext->setToken(null);

        return $logoutEvent->getResponse();
    }
}
