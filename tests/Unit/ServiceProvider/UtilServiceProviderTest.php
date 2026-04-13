<?php

declare(strict_types=1);

use GaaraHyperf\IPResolver\IPResolver;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\IPWhiteListChecker\IPWhiteListChecker;
use GaaraHyperf\IPWhiteListChecker\IPWhiteListCheckerInterface;
use GaaraHyperf\ServiceProvider\UtilServiceProvider;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers util services into container', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('set')->once()->with(
        IPResolverInterface::class,
        Mockery::on(fn (mixed $service) => $service instanceof IPResolver)
    );

    $container->shouldReceive('set')->once()->with(
        IPWhiteListCheckerInterface::class,
        Mockery::on(fn (mixed $service) => $service instanceof IPWhiteListChecker)
    );

    $provider = new UtilServiceProvider();
    $provider->register($container);

    expect(true)->toBeTrue();
});
