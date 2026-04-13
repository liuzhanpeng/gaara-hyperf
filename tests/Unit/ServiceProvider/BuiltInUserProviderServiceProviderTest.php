<?php

declare(strict_types=1);

use GaaraHyperf\ServiceProvider\BuiltInUserProviderServiceProvider;
use GaaraHyperf\UserProvider\Builder\MemoryUserProviderBuilder;
use GaaraHyperf\UserProvider\Builder\ModelUserProviderBuilder;
use GaaraHyperf\UserProvider\UserProviderFactory;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers built-in user provider builders', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderFactory $factory */
    $factory = Mockery::mock(UserProviderFactory::class);

    $container->shouldReceive('get')->once()->with(UserProviderFactory::class)->andReturn($factory);

    $factory->shouldReceive('registerBuilder')->once()->with('memory', MemoryUserProviderBuilder::class);
    $factory->shouldReceive('registerBuilder')->once()->with('model', ModelUserProviderBuilder::class);

    $provider = new BuiltInUserProviderServiceProvider();
    $provider->register($container);

    expect(true)->toBeTrue();
});
