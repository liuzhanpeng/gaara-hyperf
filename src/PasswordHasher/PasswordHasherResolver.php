<?php

declare(strict_types=1);

namespace GaaraHyperf\PasswordHasher;

use Psr\Container\ContainerInterface;

/**
 * 密码哈希器解析器
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
    public function resolve(string $name = 'default'): PasswordHasherInterface
    {
        if (!isset($this->passwordHasherMap[$name])) {
            throw new \InvalidArgumentException(sprintf('Password hasher "%s" is not defined', $name));
        }

        $passwordHasherId = $this->passwordHasherMap[$name];
        $passwordHasher = $this->container->get($passwordHasherId);
        if (!$passwordHasher instanceof PasswordHasherInterface) {
            throw new \LogicException(sprintf('Password hasher "%s" must implement PasswordHasherInterface', $passwordHasherId));
        }

        return $passwordHasher;
    }
}
