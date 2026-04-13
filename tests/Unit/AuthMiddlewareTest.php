<?php

declare(strict_types=1);

use GaaraHyperf\AuthMiddleware;
use GaaraHyperf\GuardManager;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

afterEach(function (): void {
    Mockery::close();
});

it('returns guard manager response when authentication flow short-circuits', function (): void {
    /** @var GuardManager&MockInterface $guardManager */
    $guardManager = Mockery::mock(GuardManager::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&RequestHandlerInterface $handler */
    $handler = Mockery::mock(RequestHandlerInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $guardManager->shouldReceive('process')->once()->with($request)->andReturn($response);
    $handler->shouldNotReceive('handle');

    $middleware = new AuthMiddleware($guardManager);

    expect($middleware->process($request, $handler))->toBe($response);
});

it('delegates to next handler when guard manager returns null', function (): void {
    /** @var GuardManager&MockInterface $guardManager */
    $guardManager = Mockery::mock(GuardManager::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&RequestHandlerInterface $handler */
    $handler = Mockery::mock(RequestHandlerInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $guardManager->shouldReceive('process')->once()->with($request)->andReturn(null);
    $handler->shouldReceive('handle')->once()->with($request)->andReturn($response);

    $middleware = new AuthMiddleware($guardManager);

    expect($middleware->process($request, $handler))->toBe($response);
});
