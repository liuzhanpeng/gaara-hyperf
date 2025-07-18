<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfToken;

use Psr\Container\ContainerInterface;

/**
 * 内置的CSRF令牌管理器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CsrfTokenManagerResolver implements CsrfTokenManagerResolverInterface
{
    public function __construct(
        private array $csrfTokenManagerMap,
        private ContainerInterface $container,
    ) {}

    /**
     * @inheritDoc
     */
    public function resolve(string $name = 'default'): CsrfTokenManagerInterface
    {
        if (!isset($this->csrfTokenManagerMap[$name])) {
            throw new \InvalidArgumentException("CSRF Token管理器不存在: $name");
        }

        $csrfTokenManagerId = $this->csrfTokenManagerMap[$name];
        $csrfTokenManager = $this->container->get($csrfTokenManagerId);
        if (!$csrfTokenManager instanceof CsrfTokenManagerInterface) {
            throw new \LogicException(sprintf('CSRF Token管理器 "%s" 必须实现 %s 接口', $csrfTokenManagerId, CsrfTokenManagerInterface::class));
        }

        return $csrfTokenManager;
    }
}
