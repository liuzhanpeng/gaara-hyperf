<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\PasswordHasher\PasswordHasherFactory;
use GaaraHyperf\PasswordHasher\PasswordHasherResolver;
use GaaraHyperf\PasswordHasher\PasswordHasherResolverInterface;
use Hyperf\Contract\ContainerInterface;

/**
 * 密码哈希器服务提供者.
 */
class PasswordHasherServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $gaaraConfig = $container->get(ConfigLoaderInterface::class)->load();
        $configGroup = ($gaaraConfig->serviceConfig('password_hashers') ?? []) + [
            'default' => [
                'type' => 'default',
                'algo' => PASSWORD_DEFAULT,
            ],
        ];

        $factories = [];
        foreach ($configGroup as $name => $config) {
            $factories[$name] = fn () => $container->get(PasswordHasherFactory::class)->create($config);
        }

        $container->set(PasswordHasherResolverInterface::class, new PasswordHasherResolver($factories));
    }
}
