<?php

declare(strict_types=1);

use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\Token\TokenInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('returns token from constructor', function (): void {
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $opaqueToken = new OpaqueToken($token, 'abc123', 1000, 3600);

    expect($opaqueToken->token())->toBe($token);
});

it('returns access token from constructor', function (): void {
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $opaqueToken = new OpaqueToken($token, 'abc123', 1000, 3600);

    expect($opaqueToken->accessToken())->toBe('abc123');
});

it('returns issuedAt from constructor', function (): void {
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $opaqueToken = new OpaqueToken($token, 'abc123', 1000, 3600);

    expect($opaqueToken->issuedAt())->toBe(1000);
});

it('returns expiresIn from constructor', function (): void {
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $opaqueToken = new OpaqueToken($token, 'abc123', 1000, 3600);

    expect($opaqueToken->expiresIn())->toBe(3600);
});

it('is not expired when within ttl', function (): void {
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $opaqueToken = new OpaqueToken($token, 'abc123', time(), 3600);

    expect($opaqueToken->isExpired())->toBeFalse();
});

it('is expired when ttl has elapsed', function (): void {
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $opaqueToken = new OpaqueToken($token, 'abc123', time() - 3601, 3600);

    expect($opaqueToken->isExpired())->toBeTrue();
});
