<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuar\TokenStorage;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageResolverInterface;

/**
 * 内置的TokenStorage解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class TokenStorageResolver implements TokenStorageResolverInterface
{
    public function __construct(
        private array $tokenStorageMap,
        private ContainerInterface $container
    ) {}

    /**
     * @inheritDoc
     */
    public function resolve(string $guardName): TokenStorageInterface
    {
        if (!isset($this->tokenStorageMap[$guardName])) {
            throw new \InvalidArgumentException("TokenStorage for guard '$guardName' not found");
        }

        $tokenStorageId = $this->tokenStorageMap[$guardName];
        $tokenStorage = $this->container->get($tokenStorageId);
        if (!$tokenStorage instanceof TokenStorageInterface) {
            throw new \InvalidArgumentException("TokenStorage '$tokenStorageId' must be an instance of TokenStorageInterface");
        }

        return $tokenStorage;
    }
}
