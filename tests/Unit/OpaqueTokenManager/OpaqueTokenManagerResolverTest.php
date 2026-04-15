<?php

declare(strict_types=1);

use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolver;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('throws when requested opaque token manager is not defined', function (): void {
    $resolver = new OpaqueTokenManagerResolver([]);

    expect(fn () => $resolver->resolve('unknown'))
        ->toThrow(InvalidArgumentException::class, 'Opaque Token Manager does not exist: unknown');
});

it('resolves opaque token manager by name', function (): void {
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);

    $resolver = new OpaqueTokenManagerResolver([
        'default' => fn () => $manager,
    ]);

    expect($resolver->resolve('default'))->toBe($manager)
        ->and($resolver->resolve())->toBe($manager);
});

it('caches resolved opaque token manager instance', function (): void {
    $calls = 0;
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);

    $resolver = new OpaqueTokenManagerResolver([
        'default' => function () use (&$calls, $manager) {
            ++$calls;
            return $manager;
        },
    ]);

    $first = $resolver->resolve();
    $second = $resolver->resolve('default');

    expect($first)->toBe($manager)
        ->and($second)->toBe($manager)
        ->and($calls)->toBe(1);
});
