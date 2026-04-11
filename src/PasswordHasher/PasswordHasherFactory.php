<?php

declare(strict_types=1);

namespace GaaraHyperf\PasswordHasher;

use GaaraHyperf\Config\CustomConfig;
use Hyperf\Contract\ContainerInterface;
use InvalidArgumentException;

/**
 * 密码哈希器服务工厂
 */
class PasswordHasherFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function create(array $config): PasswordHasherInterface
    {
        $type = $config['type'] ?? 'default';
        unset($config['type']);

        switch ($type) {
            case 'default':
                return new DefaultPasswordHasher($config['algo'] ?? PASSWORD_BCRYPT);
            case 'custom':
                $customConfig = CustomConfig::from($config);
                $passwordHasher = $this->container->make($customConfig->class(), $customConfig->params());
                if (! $passwordHasher instanceof PasswordHasherInterface) {
                    throw new InvalidArgumentException('Custom PasswordHasher class must be an instance of PasswordHasherInterface');
                }

                return $passwordHasher;
            default:
                throw new InvalidArgumentException(sprintf('Invalid password hasher type: %s', $type));
        }
    }
}
