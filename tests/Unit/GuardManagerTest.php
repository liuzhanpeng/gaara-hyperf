<?php

declare(strict_types=1);

use GaaraHyperf\GuardInterface;
use GaaraHyperf\GuardManager;
use GaaraHyperf\GuardResolver;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('returns null when no guard supports request', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var GuardInterface&MockInterface $firstGuard */
    $firstGuard = Mockery::mock(GuardInterface::class);
    /** @var GuardInterface&MockInterface $secondGuard */
    $secondGuard = Mockery::mock(GuardInterface::class);

    $firstGuard->shouldReceive('supports')->once()->with($request)->andReturn(false);
    $firstGuard->shouldNotReceive('authenticate');

    $secondGuard->shouldReceive('supports')->once()->with($request)->andReturn(false);
    $secondGuard->shouldNotReceive('authenticate');

    $manager = new GuardManager(new GuardResolver([
        'first' => fn () => $firstGuard,
        'second' => fn () => $secondGuard,
    ]));

    expect($manager->process($request))->toBeNull();
});

it('returns response from first supporting guard and stops iterating', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    /** @var GuardInterface&MockInterface $firstGuard */
    $firstGuard = Mockery::mock(GuardInterface::class);
    /** @var GuardInterface&MockInterface $secondGuard */
    $secondGuard = Mockery::mock(GuardInterface::class);

    $firstGuard->shouldReceive('supports')->once()->with($request)->andReturn(true);
    $firstGuard->shouldReceive('authenticate')->once()->with($request)->andReturn($response);

    $secondGuard->shouldNotReceive('supports');
    $secondGuard->shouldNotReceive('authenticate');

    $manager = new GuardManager(new GuardResolver([
        'first' => fn () => $firstGuard,
        'second' => fn () => $secondGuard,
    ]));

    expect($manager->process($request))->toBe($response);
});

it('continues to next guard when supporting guard returns null', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    /** @var GuardInterface&MockInterface $firstGuard */
    $firstGuard = Mockery::mock(GuardInterface::class);
    /** @var GuardInterface&MockInterface $secondGuard */
    $secondGuard = Mockery::mock(GuardInterface::class);

    $firstGuard->shouldReceive('supports')->once()->with($request)->andReturn(true);
    $firstGuard->shouldReceive('authenticate')->once()->with($request)->andReturn(null);

    $secondGuard->shouldReceive('supports')->once()->with($request)->andReturn(true);
    $secondGuard->shouldReceive('authenticate')->once()->with($request)->andReturn($response);

    $manager = new GuardManager(new GuardResolver([
        'first' => fn () => $firstGuard,
        'second' => fn () => $secondGuard,
    ]));

    expect($manager->process($request))->toBe($response);
});
