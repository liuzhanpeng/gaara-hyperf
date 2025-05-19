<?php

declare(strict_types=1);

use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\User\UserInterface;

test('authenticated token', function () {
    $user = Mockery::mock(UserInterface::class);

    $token = new AuthenticatedToken('test', $user, []);

    expect($token)->toBeInstanceOf(AuthenticatedToken::class);
    expect($token->getGuardName())->toBe('test');
    expect($token->getUser())->toBe($user);
    expect($token->hasAttribute('attr'))->toBeFalse();

    $token->setAttribute('attr', 'value');
    expect($token->hasAttribute('attr'))->toBeTrue();
    expect($token->getAttribute('attr'))->toBe('value');
});
