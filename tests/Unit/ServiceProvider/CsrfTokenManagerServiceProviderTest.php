<?php

declare(strict_types=1);

use GaaraHyperf\Config\Config;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerFactory;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolver;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolverInterface;
use GaaraHyperf\ServiceProvider\CsrfTokenManagerServiceProvider;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers csrf token manager resolver and supports default resolver factory', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var ConfigLoaderInterface&MockInterface $loader */
    $loader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var CsrfTokenManagerFactory&MockInterface $factory */
    $factory = Mockery::mock(CsrfTokenManagerFactory::class);
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);

    $loader->shouldReceive('load')->once()->andReturn(new Config([], []));

    $container->shouldReceive('get')->once()->with(ConfigLoaderInterface::class)->andReturn($loader);
    $container->shouldReceive('get')->once()->with(CsrfTokenManagerFactory::class)->andReturn($factory);

    $factory->shouldReceive('create')->once()->with(Mockery::on(function (array $config): bool {
        return $config['type'] === 'session' && $config['prefix'] === 'default';
    }))->andReturn($manager);

    $capturedResolver = null;
    $container->shouldReceive('set')->once()->with(
        CsrfTokenManagerResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedResolver): bool {
            $capturedResolver = $resolver;
            return $resolver instanceof CsrfTokenManagerResolver;
        })
    );

    $provider = new CsrfTokenManagerServiceProvider();
    $provider->register($container);

    expect($capturedResolver)->toBeInstanceOf(CsrfTokenManagerResolver::class)
        ->and($capturedResolver->resolve('default'))->toBe($manager);
});
