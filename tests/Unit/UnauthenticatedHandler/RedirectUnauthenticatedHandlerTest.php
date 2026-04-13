<?php

declare(strict_types=1);

use GaaraHyperf\UnauthenticatedHandler\RedirectUnauthenticatedHandler;
use Hyperf\Contract\SessionInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HyperfResponseInterface;
use Hyperf\Session\Session;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

afterEach(function (): void {
    Mockery::close();
});

it('throws when target path is empty', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);

    expect(fn () => new RedirectUnauthenticatedHandler($response, $session, ''))
        ->toThrow(InvalidArgumentException::class, 'target_path');
});

it('flashes message and redirects with encoded return path when redirect enabled', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);
    /** @var MockInterface&ResponseInterface $redirectResponse */
    $redirectResponse = Mockery::mock(ResponseInterface::class);

    $request = createRedirectUnauthenticatedHandlerTestRequest('/private/page');

    $session->shouldReceive('flash')->once()->with('authentication_error', 'Please login first');
    $response->shouldReceive('redirect')->once()->with('/login?redirect_to=%2Fprivate%2Fpage')->andReturn($redirectResponse);

    $handler = new RedirectUnauthenticatedHandler(
        response: $response,
        session: $session,
        targetPath: '/login',
        redirectEnabled: true,
        redirectField: 'redirect_to',
        errorField: 'authentication_error',
        errorMessage: 'Please login first',
    );

    expect($handler->handle($request, null))->toBe($redirectResponse);
});

it('redirects without query when redirect is disabled', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);
    /** @var MockInterface&ResponseInterface $redirectResponse */
    $redirectResponse = Mockery::mock(ResponseInterface::class);

    $request = createRedirectUnauthenticatedHandlerTestRequest('/private/page');

    $session->shouldReceive('flash')->once()->with('authentication_error', 'Please login first');
    $response->shouldReceive('redirect')->once()->with('/login')->andReturn($redirectResponse);

    $handler = new RedirectUnauthenticatedHandler(
        response: $response,
        session: $session,
        targetPath: '/login',
        redirectEnabled: false,
        errorMessage: 'Please login first',
    );

    expect($handler->handle($request, null))->toBe($redirectResponse);
});

it('does not flash when session is not concrete Session implementation', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);
    /** @var MockInterface&ResponseInterface $redirectResponse */
    $redirectResponse = Mockery::mock(ResponseInterface::class);

    $request = createRedirectUnauthenticatedHandlerTestRequest('/private/page');

    $response->shouldReceive('redirect')->once()->with('/login?next=%2Fprivate%2Fpage')->andReturn($redirectResponse);

    $handler = new RedirectUnauthenticatedHandler(
        response: $response,
        session: $session,
        targetPath: '/login',
        redirectEnabled: true,
        redirectField: 'next',
        errorMessage: 'Please login first',
    );

    expect($handler->handle($request, null))->toBe($redirectResponse);
});

function createRedirectUnauthenticatedHandlerTestRequest(string $path): ServerRequestInterface
{
    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('getPath')->andReturn($path);

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getUri')->andReturn($uri);

    return $request;
}
