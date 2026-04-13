<?php

declare(strict_types=1);

use GaaraHyperf\Exception\UnauthenticatedException;
use GaaraHyperf\UnauthenticatedHandler\DefaultUnauthenticatedHandler;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('always throws unauthenticated exception', function (): void {
    $handler = new DefaultUnauthenticatedHandler();
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    expect(fn () => $handler->handle($request, null))
        ->toThrow(UnauthenticatedException::class, 'Unauthenticated');
});
