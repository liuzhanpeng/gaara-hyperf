<?php

declare(strict_types=1);

use GaaraHyperf\PasswordHasher\PasswordHasherInterface;
use GaaraHyperf\PasswordHasher\PasswordHasherResolver;

it('throws when requested hasher is not defined', function (): void {
    $resolver = new PasswordHasherResolver([]);

    expect(fn () => $resolver->resolve('unknown'))
        ->toThrow(InvalidArgumentException::class, 'is not defined');
});

it('resolves hasher by name and caches created instance', function (): void {
    $calls = 0;
    $hasher = new class implements PasswordHasherInterface {
        public function hash(string $password): string
        {
            return 'h:' . $password;
        }

        public function verify(string $password, string $hashedPassword): bool
        {
            return $hashedPassword === 'h:' . $password;
        }
    };

    $resolver = new PasswordHasherResolver([
        'default' => function () use (&$calls, $hasher) {
            ++$calls;
            return $hasher;
        },
    ]);

    $first = $resolver->resolve();
    $second = $resolver->resolve('default');

    expect($first)->toBe($hasher)
        ->and($second)->toBe($hasher)
        ->and($calls)->toBe(1);
});
