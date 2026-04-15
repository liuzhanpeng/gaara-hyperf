<?php

declare(strict_types=1);

use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerResolver;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('throws when requested issuer name is not defined', function (): void {
    $resolver = new OpaqueTokenIssuerResolver([]);

    expect(fn () => $resolver->resolve('unknown'))
        ->toThrow(InvalidArgumentException::class, 'Opaque Token Issuer does not exist: unknown');
});

it('resolves issuer by name via factory closure', function (): void {
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);

    $resolver = new OpaqueTokenIssuerResolver([
        'default' => fn () => $issuer,
    ]);

    expect($resolver->resolve('default'))->toBe($issuer);
});

it('resolves default issuer when no name given', function (): void {
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);

    $resolver = new OpaqueTokenIssuerResolver([
        'default' => fn () => $issuer,
    ]);

    expect($resolver->resolve())->toBe($issuer);
});

it('calls factory closure only once and caches the instance', function (): void {
    $calls = 0;
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);

    $resolver = new OpaqueTokenIssuerResolver([
        'default' => function () use (&$calls, $issuer) {
            ++$calls;
            return $issuer;
        },
    ]);

    $first = $resolver->resolve();
    $second = $resolver->resolve('default');

    expect($first)->toBe($issuer)
        ->and($second)->toBe($issuer)
        ->and($calls)->toBe(1);
});
