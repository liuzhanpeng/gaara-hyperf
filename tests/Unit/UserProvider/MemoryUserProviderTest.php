<?php

declare(strict_types=1);

use GaaraHyperf\User\MemoryUser;
use GaaraHyperf\UserProvider\MemoryUserProvider;

it('returns memory user when identifier exists', function (): void {
    $provider = new MemoryUserProvider([
        'alice' => ['password' => 'secret'],
        'bob' => ['password' => 'pwd'],
    ]);

    $user = $provider->findByIdentifier('alice');

    expect($user)->toBeInstanceOf(MemoryUser::class)
        ->and($user?->getIdentifier())->toBe('alice')
        ->and($user?->getPassword())->toBe('secret');
});

it('returns null when identifier does not exist', function (): void {
    $provider = new MemoryUserProvider([
        'alice' => ['password' => 'secret'],
    ]);

    expect($provider->findByIdentifier('charlie'))->toBeNull();
});

it('throws when user info misses password field', function (): void {
    $provider = new MemoryUserProvider([
        'alice' => ['roles' => ['ROLE_USER']],
    ]);

    expect(fn () => $provider->findByIdentifier('alice'))
        ->toThrow(InvalidArgumentException::class, 'password');
});
