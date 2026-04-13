<?php

declare(strict_types=1);

use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolver;
use GaaraHyperf\Token\TokenInterface;

it('throws when requested opaque token manager is not defined', function (): void {
    $resolver = new OpaqueTokenManagerResolver([]);

    expect(fn () => $resolver->resolve('unknown'))
        ->toThrow(InvalidArgumentException::class, 'Opaque Token Manager does not exist');
});

it('resolves opaque token manager by name and caches instance', function (): void {
    $calls = 0;
    $manager = new class implements OpaqueTokenManagerInterface {
        public function issue(TokenInterface $token): string
        {
            return 'token';
        }

        public function resolve(string $accessToken): ?TokenInterface
        {
            return null;
        }

        public function revoke(string $accessToken): void
        {
        }
    };

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
