<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\PasswordHasher;

use Hyperf\Contract\ContainerInterface;

/**
 * 内置的密码哈希器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordHasherResolver implements PasswordHasherResolverInterface
{
    public function __construct(
        private array $passwordHasherMap,
        private ContainerInterface $container
    ) {}

    /**
     * @@inheritDoc
     */
    public function resolve(string $guardName): PasswordHasherInterface
    {
        if (!isset($this->passwordHasherMap[$guardName])) {
            throw new \InvalidArgumentException("密码哈希器不存在: $guardName");
        }

        $passwordHasherId = $this->passwordHasherMap[$guardName];
        $passwordHasher = $this->container->get($passwordHasherId);
        if (!$passwordHasher instanceof PasswordHasherInterface) {
            throw new \LogicException(sprintf('Guard %s password hasher must be an instance of %s', $guardName, PasswordHasherInterface::class));
        }

        return $passwordHasher;
    }
}
