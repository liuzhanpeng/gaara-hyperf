<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\Constants;
use GaaraHyperf\PasswordHasher\PasswordHasherFactory;
use GaaraHyperf\PasswordHasher\PasswordHasherResolver;
use GaaraHyperf\PasswordHasher\PasswordHasherResolverInterface;

/**
 * 密码哈希器服务提供者
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordHasherServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $config = $container->get(ConfigLoaderInterface::class)->load();

        $passwordHasherConfig = array_merge([
            'default' => [
                'type' => 'default',
                'algo' => PASSWORD_DEFAULT,
            ]
        ], $config->serviceConfig('password_hashers') ?? []);

        $passwordHasherMap = [];
        foreach ($passwordHasherConfig as $name => $config) {
            $passwordHasherMap[$name] = sprintf('%s.%s', Constants::PASSWORD_HASHER_PREFIX, $name);
            $container->define($passwordHasherMap[$name], fn() => $container->get(PasswordHasherFactory::class)->create($config));
        }

        $container->define(PasswordHasherResolverInterface::class, fn() => new PasswordHasherResolver($passwordHasherMap, $container));
    }
}
