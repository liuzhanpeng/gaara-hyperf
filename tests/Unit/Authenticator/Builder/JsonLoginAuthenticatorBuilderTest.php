<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\Builder\JsonLoginAuthenticatorBuilder;
use GaaraHyperf\Authenticator\JsonLoginAuthenticator;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('throws when check path option is missing', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $builder = new JsonLoginAuthenticatorBuilder($container);

    expect(fn () => $builder->create([], $userProvider, new EventDispatcher()))
        ->toThrow(InvalidArgumentException::class, 'check_path');
});

it('creates authenticator with defaults and supports matching request', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $builder = new JsonLoginAuthenticatorBuilder($container);
    $authenticator = $builder->create(['check_path' => '/json-login'], $userProvider, new EventDispatcher());

    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('getPath')->andReturn('/json-login');

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturn('application/json');
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn('POST');

    expect($authenticator)->toBeInstanceOf(JsonLoginAuthenticator::class)
        ->and($authenticator->supports($request))->toBeTrue();
});
