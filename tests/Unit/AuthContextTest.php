<?php

declare(strict_types=1);

use GaaraHyperf\AuthContext;
use GaaraHyperf\Exception\UnauthenticatedException;
use GaaraHyperf\GuardInterface;
use GaaraHyperf\GuardResolver;
use GaaraHyperf\Token\TokenContextInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('delegates login to resolved guard', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&TokenContextInterface $tokenContext */
    $tokenContext = Mockery::mock(TokenContextInterface::class);
    /** @var GuardInterface&MockInterface $guard */
    $guard = Mockery::mock(GuardInterface::class);
    /** @var MockInterface&UserInterface $user */
    $user = Mockery::mock(UserInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $guard->shouldReceive('authenticateUser')->once()->with($user, $request, null, [])->andReturn($response);

    $resolver = new GuardResolver(['web' => fn () => $guard]);
    $context = new AuthContext($request, $tokenContext, $resolver);

    expect($context->login($user, 'web'))->toBe($response);
});

it('throws unauthenticated exception on logout when no token exists', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&TokenContextInterface $tokenContext */
    $tokenContext = Mockery::mock(TokenContextInterface::class);

    $tokenContext->shouldReceive('getToken')->andReturnNull();

    $resolver = new GuardResolver([]);
    $context = new AuthContext($request, $tokenContext, $resolver);

    expect(fn () => $context->logout())->toThrow(UnauthenticatedException::class);
});

it('delegates logout to token guard when authenticated', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&TokenContextInterface $tokenContext */
    $tokenContext = Mockery::mock(TokenContextInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var GuardInterface&MockInterface $guard */
    $guard = Mockery::mock(GuardInterface::class);
    /** @var MockInterface&UserProviderInterface $provider */
    $provider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&UserInterface $user */
    $user = Mockery::mock(UserInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $tokenContext->shouldReceive('getToken')->andReturn($token);
    $token->shouldReceive('getGuardName')->andReturn('api');
    $token->shouldReceive('getUserIdentifier')->andReturn('u1');
    $guard->shouldReceive('isTokenAuthenticated')->andReturnTrue();
    $guard->shouldReceive('getUserProvider')->andReturn($provider);
    $provider->shouldReceive('findByIdentifier')->once()->with('u1')->andReturn($user);
    $guard->shouldReceive('logout')->once()->with($token)->andReturn($response);

    $resolver = new GuardResolver(['api' => fn () => $guard]);
    $context = new AuthContext($request, $tokenContext, $resolver);

    expect($context->logout())->toBe($response);
});

it('caches loaded user between getUser calls', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&TokenContextInterface $tokenContext */
    $tokenContext = Mockery::mock(TokenContextInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var GuardInterface&MockInterface $guard */
    $guard = Mockery::mock(GuardInterface::class);
    /** @var MockInterface&UserProviderInterface $provider */
    $provider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&UserInterface $user */
    $user = Mockery::mock(UserInterface::class);

    $tokenContext->shouldReceive('getToken')->andReturn($token);
    $token->shouldReceive('getGuardName')->andReturn('api');
    $token->shouldReceive('getUserIdentifier')->andReturn('u1');
    $guard->shouldReceive('getUserProvider')->andReturn($provider);
    $provider->shouldReceive('findByIdentifier')->once()->with('u1')->andReturn($user);

    $resolver = new GuardResolver(['api' => fn () => $guard]);
    $context = new AuthContext($request, $tokenContext, $resolver);

    expect($context->getUser())->toBe($user);
    expect($context->getUser())->toBe($user);
});

it('returns authentication state based on token trust and loaded user', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&TokenContextInterface $tokenContext */
    $tokenContext = Mockery::mock(TokenContextInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var GuardInterface&MockInterface $guard */
    $guard = Mockery::mock(GuardInterface::class);
    /** @var MockInterface&UserProviderInterface $provider */
    $provider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&UserInterface $user */
    $user = Mockery::mock(UserInterface::class);

    $tokenContext->shouldReceive('getToken')->andReturn($token);
    $token->shouldReceive('getGuardName')->andReturn('api');
    $token->shouldReceive('getUserIdentifier')->andReturn('u1');
    $guard->shouldReceive('isTokenAuthenticated')->andReturnTrue();
    $guard->shouldReceive('getUserProvider')->andReturn($provider);
    $provider->shouldReceive('findByIdentifier')->once()->with('u1')->andReturn($user);

    $resolver = new GuardResolver(['api' => fn () => $guard]);
    $context = new AuthContext($request, $tokenContext, $resolver);

    expect($context->isAuthenticated())->toBeTrue();
});

it('delegates isGranted to guard when authenticated', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&TokenContextInterface $tokenContext */
    $tokenContext = Mockery::mock(TokenContextInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var GuardInterface&MockInterface $guard */
    $guard = Mockery::mock(GuardInterface::class);
    /** @var MockInterface&UserProviderInterface $provider */
    $provider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&UserInterface $user */
    $user = Mockery::mock(UserInterface::class);

    $tokenContext->shouldReceive('getToken')->andReturn($token);
    $token->shouldReceive('getGuardName')->andReturn('api');
    $token->shouldReceive('getUserIdentifier')->andReturn('u1');
    $guard->shouldReceive('isTokenAuthenticated')->andReturnTrue();
    $guard->shouldReceive('getUserProvider')->andReturn($provider);
    $provider->shouldReceive('findByIdentifier')->once()->with('u1')->andReturn($user);
    $guard->shouldReceive('isGranted')->once()->with($token, 'ROLE_ADMIN', 'dashboard')->andReturnTrue();

    $resolver = new GuardResolver(['api' => fn () => $guard]);
    $context = new AuthContext($request, $tokenContext, $resolver);

    expect($context->isGranted('ROLE_ADMIN', 'dashboard'))->toBeTrue();
});
