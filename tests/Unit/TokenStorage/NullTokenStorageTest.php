<?php

declare(strict_types=1);

use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\TokenStorage\NullTokenStorage;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('always returns null on get', function (): void {
    $storage = new NullTokenStorage();

    expect($storage->get('any-key'))->toBeNull();
});

it('accepts set and delete operations without side effects', function (): void {
    $storage = new NullTokenStorage();
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $storage->set('k', $token);
    $storage->delete('k');

    expect($storage->get('k'))->toBeNull();
});
