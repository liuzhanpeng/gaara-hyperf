<?php

declare(strict_types=1);

use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\Token\TokenContext;

it('returns null when token is not set in context', function (): void {
    $context = new TokenContext('gaara.test.ctx.null');

    expect($context->getToken())->toBeNull();
});

it('stores and retrieves token by namespaced key', function (): void {
    $prefix = 'gaara.test.ctx.' . uniqid('token_', true);
    $context = new TokenContext($prefix);

    $token = new AuthenticatedToken('web', 'user-1');
    $context->setToken($token);

    expect($context->getToken())->toBe($token);

    $context->setToken(null);
    expect($context->getToken())->toBeNull();
});
