<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\Builder\OpaqueTokenAuthenticatorBuilder;
use GaaraHyperf\Authenticator\OpaqueTokenAuthenticator;
use GaaraHyperf\Event\LogoutEvent;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('creates OpaqueTokenAuthenticator with default token manager', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $container->shouldReceive('get')->once()->with(OpaqueTokenManagerResolverInterface::class)->andReturn($resolver);
    $resolver->shouldReceive('resolve')->once()->with('default')->andReturn($manager);

    $authenticator = (new OpaqueTokenAuthenticatorBuilder($container))
        ->create([], $userProvider, new EventDispatcher());

    expect($authenticator)->toBeInstanceOf(OpaqueTokenAuthenticator::class);
});

it('creates OpaqueTokenAuthenticator with custom token manager name', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $container->shouldReceive('get')->once()->with(OpaqueTokenManagerResolverInterface::class)->andReturn($resolver);
    $resolver->shouldReceive('resolve')->once()->with('redis')->andReturn($manager);

    $authenticator = (new OpaqueTokenAuthenticatorBuilder($container))
        ->create(['token_manager' => 'redis'], $userProvider, new EventDispatcher());

    expect($authenticator)->toBeInstanceOf(OpaqueTokenAuthenticator::class);
});

it('registers logout listener that revokes token on POST request', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $container->shouldReceive('get')->once()->with(OpaqueTokenManagerResolverInterface::class)->andReturn($resolver);
    $resolver->shouldReceive('resolve')->once()->with('default')->andReturn($manager);
    $request->shouldReceive('getMethod')->once()->andReturn('POST');
    $manager->shouldReceive('revoke')->once()->with($request);

    $dispatcher = new EventDispatcher();
    (new OpaqueTokenAuthenticatorBuilder($container))
        ->create([], $userProvider, $dispatcher);

    $dispatcher->dispatch(new LogoutEvent($token, $request));
});

it('registered logout listener ignores non-POST logout requests', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $container->shouldReceive('get')->once()->with(OpaqueTokenManagerResolverInterface::class)->andReturn($resolver);
    $resolver->shouldReceive('resolve')->once()->with('default')->andReturn($manager);
    $request->shouldReceive('getMethod')->once()->andReturn('GET');
    $manager->shouldNotReceive('revoke');

    $dispatcher = new EventDispatcher();
    (new OpaqueTokenAuthenticatorBuilder($container))
        ->create([], $userProvider, $dispatcher);

    $dispatcher->dispatch(new LogoutEvent($token, $request));
});
