<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceFactory;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Config\PasswordHasherConfig;
use Lzpeng\HyperfAuthGuard\PasswordHasher\DefaultPasswordHasher;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherInterface;

/**
 * 密码哈希器服务工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordHasherFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(PasswordHasherConfig $passwordHasherConfig): PasswordHasherInterface
    {
        $type = $passwordHasherConfig->type();
        $options = $passwordHasherConfig->options();

        switch ($type) {
            case 'default':
                return new DefaultPasswordHasher($options['algo'] ?? PASSWORD_BCRYPT);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $passwordHasher = $this->container->make($customConfig->class(), $customConfig->args());
                if (!$passwordHasher instanceof PasswordHasherInterface) {
                    throw new \LogicException('Custom PasswordHasher class must be an instance of PasswordHasherInterface');
                }

                return $passwordHasher;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid password hasher type: %s', $type));
        }
    }
}
