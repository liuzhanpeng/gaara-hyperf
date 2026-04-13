<?php

declare(strict_types=1);

use GaaraHyperf\CsrfTokenManager\CsrfToken;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolver;

it('throws when requested csrf token manager is not defined', function (): void {
    $resolver = new CsrfTokenManagerResolver([]);

    expect(fn () => $resolver->resolve('unknown'))
        ->toThrow(InvalidArgumentException::class, 'CSRF Token Manager does not exist');
});

it('resolves csrf token manager by name and caches instance', function (): void {
    $calls = 0;
    $manager = new class implements CsrfTokenManagerInterface {
        public function generate(string $tokenId = 'authenticate'): CsrfToken
        {
            return new CsrfToken($tokenId, 'v');
        }

        public function verify(CsrfToken $token): bool
        {
            return true;
        }
    };

    $resolver = new CsrfTokenManagerResolver([
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
