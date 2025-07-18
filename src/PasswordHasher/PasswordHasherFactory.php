<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\PasswordHasher;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;

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

    public function create(array $passwordHasherConfig): PasswordHasherInterface
    {
        if (count($passwordHasherConfig) !== 1) {
            throw new \InvalidArgumentException('password_hasher config must be an associative array with a single key-value pair');
        }

        $type = array_key_first($passwordHasherConfig);
        $options = $passwordHasherConfig[$type];

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
