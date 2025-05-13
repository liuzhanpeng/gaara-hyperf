<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Logout;

use Hyperf\Contract\ContainerInterface;

/**
 * 内置的登出处理器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LogoutHandlerResolver
{
    public function __construct(
        private array $logoutHandlerMap,
        private ContainerInterface $container
    ) {}

    /**
     * @@inheritDoc
     */
    public function resolve(string $guardName): LogoutHandlerInterface
    {
        $logoutHandlerId = $this->logoutHandlerMap[$guardName] ?? null;
        if (!$logoutHandlerId) {
            throw new \InvalidArgumentException(sprintf('Guard %s logout handler not found', $guardName));
        }

        $logoutHandler = $this->container->get($logoutHandlerId);
        if (!$logoutHandler instanceof LogoutHandlerInterface) {
            throw new \InvalidArgumentException(sprintf('Guard %s logout handler must be an instance of %s', $guardName, LogoutHandlerInterface::class));
        }

        return $logoutHandler;
    }
}
