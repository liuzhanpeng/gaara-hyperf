<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\APIKeyAuthenticator;
use GaaraHyperf\Authenticator\Builder\APIKeyAuthenticatorBuilder;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('creates authenticator with default api key field', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $builder = new APIKeyAuthenticatorBuilder($container);
    $authenticator = $builder->create([], $userProvider, new EventDispatcher());

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getHeaderLine')->once()->with('X-API-KEY')->andReturn('k');

    expect($authenticator)->toBeInstanceOf(APIKeyAuthenticator::class)
        ->and($authenticator->supports($request))->toBeTrue();
});

it('creates authenticator with custom api key field', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $builder = new APIKeyAuthenticatorBuilder($container);
    $authenticator = $builder->create(['api_key_field' => 'X-CUSTOM-KEY'], $userProvider, new EventDispatcher());

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getHeaderLine')->once()->with('X-CUSTOM-KEY')->andReturn('custom');

    expect($authenticator)->toBeInstanceOf(APIKeyAuthenticator::class)
        ->and($authenticator->supports($request))->toBeTrue();
});
