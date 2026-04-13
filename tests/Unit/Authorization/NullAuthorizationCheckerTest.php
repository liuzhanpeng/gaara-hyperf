<?php

declare(strict_types=1);

use GaaraHyperf\Authorization\NullAuthorizationChecker;
use GaaraHyperf\Token\TokenInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('returns true when token is present', function (): void {
    $checker = new NullAuthorizationChecker();
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    expect($checker->check($token, 'ROLE_ADMIN'))->toBeTrue();
});
