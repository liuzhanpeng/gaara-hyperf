<?php

declare(strict_types=1);

use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\TokenStorage\SessionTokenStorage;
use Hyperf\Contract\SessionInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('gets token from session with prefixed key', function (): void {
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $session->shouldReceive('get')->once()->with('gaara:token_storage:web:user-1')->andReturn($token);

    $storage = new SessionTokenStorage($session, 'gaara:token_storage:web');

    expect($storage->get('user-1'))->toBe($token);
});

it('sets token into session with prefixed key', function (): void {
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $session->shouldReceive('set')->once()->with('gaara:token_storage:web:user-2', $token);

    $storage = new SessionTokenStorage($session, 'gaara:token_storage:web');
    $storage->set('user-2', $token);

    expect(true)->toBeTrue();
});

it('deletes token from session with prefixed key', function (): void {
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);

    $session->shouldReceive('remove')->once()->with('gaara:token_storage:web:user-3');

    $storage = new SessionTokenStorage($session, 'gaara:token_storage:web');
    $storage->delete('user-3');

    expect(true)->toBeTrue();
});
