<?php

declare(strict_types=1);

use GaaraHyperf\Config\Config;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolver;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\ServiceProvider\OpaqueTokenManagerServiceProvider;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers opaque token manager resolver and supports default resolver factory', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var ConfigLoaderInterface&MockInterface $loader */
    $loader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var MockInterface&OpaqueTokenManagerFactory $factory */
    $factory = Mockery::mock(OpaqueTokenManagerFactory::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);

    $loader->shouldReceive('load')->once()->andReturn(new Config([], []));

    $container->shouldReceive('get')->once()->with(ConfigLoaderInterface::class)->andReturn($loader);
    $container->shouldReceive('get')->once()->with(OpaqueTokenManagerFactory::class)->andReturn($factory);

    $factory->shouldReceive('create')->once()->with(Mockery::on(function (array $config): bool {
        return $config['type'] === 'default'
            && $config['prefix'] === 'default'
            && $config['ttl'] === 1200
            && $config['max_ttl'] === 86400
            && $config['token_refresh'] === true
            && $config['single_session'] === true
            && $config['ip_bind_enabled'] === false
            && $config['user_agent_bind_enabled'] === false;
    }))->andReturn($manager);

    $capturedResolver = null;
    $container->shouldReceive('set')->once()->with(
        OpaqueTokenManagerResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedResolver): bool {
            $capturedResolver = $resolver;
            return $resolver instanceof OpaqueTokenManagerResolver;
        })
    );

    $provider = new OpaqueTokenManagerServiceProvider();
    $provider->register($container);

    expect($capturedResolver)->toBeInstanceOf(OpaqueTokenManagerResolver::class)
        ->and($capturedResolver->resolve('default'))->toBe($manager);
});
