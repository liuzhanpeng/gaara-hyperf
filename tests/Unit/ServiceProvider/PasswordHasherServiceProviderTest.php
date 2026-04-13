<?php

declare(strict_types=1);

use GaaraHyperf\Config\Config;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\PasswordHasher\PasswordHasherFactory;
use GaaraHyperf\PasswordHasher\PasswordHasherInterface;
use GaaraHyperf\PasswordHasher\PasswordHasherResolver;
use GaaraHyperf\PasswordHasher\PasswordHasherResolverInterface;
use GaaraHyperf\ServiceProvider\PasswordHasherServiceProvider;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers password hasher resolver and supports default resolver factory', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var ConfigLoaderInterface&MockInterface $loader */
    $loader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var MockInterface&PasswordHasherFactory $factory */
    $factory = Mockery::mock(PasswordHasherFactory::class);
    /** @var MockInterface&PasswordHasherInterface $hasher */
    $hasher = Mockery::mock(PasswordHasherInterface::class);

    $loader->shouldReceive('load')->once()->andReturn(new Config([], []));

    $container->shouldReceive('get')->once()->with(ConfigLoaderInterface::class)->andReturn($loader);
    $container->shouldReceive('get')->once()->with(PasswordHasherFactory::class)->andReturn($factory);

    $factory->shouldReceive('create')->once()->with(Mockery::on(function (array $config): bool {
        return $config['type'] === 'default' && $config['algo'] === PASSWORD_DEFAULT;
    }))->andReturn($hasher);

    $capturedResolver = null;
    $container->shouldReceive('set')->once()->with(
        PasswordHasherResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedResolver): bool {
            $capturedResolver = $resolver;
            return $resolver instanceof PasswordHasherResolver;
        })
    );

    $provider = new PasswordHasherServiceProvider();
    $provider->register($container);

    expect($capturedResolver)->toBeInstanceOf(PasswordHasherResolver::class)
        ->and($capturedResolver->resolve('default'))->toBe($hasher);
});
