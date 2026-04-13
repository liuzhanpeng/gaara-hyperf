<?php

declare(strict_types=1);

use GaaraHyperf\UserProvider\Builder\MemoryUserProviderBuilder;
use GaaraHyperf\UserProvider\MemoryUserProvider;

it('throws when users option is missing', function (): void {
    $builder = new MemoryUserProviderBuilder();

    expect(fn () => $builder->create([]))->toThrow(InvalidArgumentException::class, 'users');
});

it('creates memory user provider when users option exists', function (): void {
    $builder = new MemoryUserProviderBuilder();

    $provider = $builder->create([
        'users' => [
            'alice' => ['password' => 'secret'],
        ],
    ]);

    expect($provider)->toBeInstanceOf(MemoryUserProvider::class)
        ->and($provider->findByIdentifier('alice')?->getIdentifier())->toBe('alice');
});
