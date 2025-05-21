<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Logout;

use Hyperf\Contract\ContainerInterface;

/**
 * 内置的登出处理器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LogoutHandlerResolver implements LogoutHandlerResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
        private array $logoutHandlerMap,
    ) {}

    /**
     * @@inheritDoc
     */
    public function resolve(string $guardName): LogoutHandlerInterface
    {
        if (!isset($this->logoutHandlerMap[$guardName])) {
            throw new \InvalidArgumentException("未找到guard:{$guardName}的登出处理器");
        }

        $logoutHandlerId = $this->logoutHandlerMap[$guardName];
        $logoutHandler = $this->container->get($logoutHandlerId);
        if (!$logoutHandler instanceof LogoutHandlerInterface) {
            throw new \LogicException(sprintf('Guard %s logout handler must be an instance of %s', $guardName, LogoutHandlerInterface::class));
        }

        return $logoutHandler;
    }
}
