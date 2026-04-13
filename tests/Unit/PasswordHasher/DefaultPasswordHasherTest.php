<?php

declare(strict_types=1);

use GaaraHyperf\PasswordHasher\DefaultPasswordHasher;

it('hashes and verifies password using default algorithm', function (): void {
    $hasher = new DefaultPasswordHasher();

    $hash = $hasher->hash('secret');

    expect($hash)->not->toBe('secret')
        ->and($hasher->verify('secret', $hash))->toBeTrue()
        ->and($hasher->verify('wrong', $hash))->toBeFalse();
});

it('supports custom algorithm parameter', function (): void {
    $hasher = new DefaultPasswordHasher(PASSWORD_DEFAULT);

    $hash = $hasher->hash('another-secret');

    expect($hasher->verify('another-secret', $hash))->toBeTrue();
});
