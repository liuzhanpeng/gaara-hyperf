<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\OpaqueTokenAuthenticator;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('supports request when opaque token can be resolved', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $opaqueToken = new OpaqueToken($token, 'tok', time(), 3600);
    $manager->shouldReceive('resolve')->once()->with($request)->andReturn($opaqueToken);

    $authenticator = makeOpaqueTokenAuthenticator(opaqueTokenManager: $manager);

    expect($authenticator->supports($request))->toBeTrue();
});

it('does not support request when opaque token cannot be resolved', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    $manager->shouldReceive('resolve')->once()->with($request)->andReturnNull();

    $authenticator = makeOpaqueTokenAuthenticator(opaqueTokenManager: $manager);

    expect($authenticator->supports($request))->toBeFalse();
});

it('throws InvalidCredentialsException when resolve returns null during authenticate', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    $manager->shouldReceive('resolve')->once()->with($request)->andReturnNull();

    $authenticator = makeOpaqueTokenAuthenticator(opaqueTokenManager: $manager);

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'Invalid access token');
});

it('returns passport with user identifier on successful authenticate', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&UserInterface $user */
    $user = Mockery::mock(UserInterface::class);

    $opaqueToken = new OpaqueToken($token, 'tok', time(), 3600);
    $token->shouldReceive('getUserIdentifier')->andReturn('user-1');
    $manager->shouldReceive('resolve')->once()->with($request)->andReturn($opaqueToken);
    $userProvider->shouldReceive('findByIdentifier')->once()->with('user-1')->andReturn($user);
    $user->shouldReceive('getIdentifier')->andReturn('user-1');

    $authenticator = makeOpaqueTokenAuthenticator(
        userProvider: $userProvider,
        opaqueTokenManager: $manager,
    );

    $passport = $authenticator->authenticate($request);

    expect($passport->getUserIdentifier())->toBe('user-1')
        ->and($passport->getUser()->getIdentifier())->toBe('user-1');
});

it('is non-interactive', function (): void {
    expect(makeOpaqueTokenAuthenticator()->isInteractive())->toBeFalse();
});

it('returns 401 response on authentication failure when no failure handler is set', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $response = makeOpaqueTokenAuthenticator()
        ->onAuthenticationFailure('api', $request, new AuthenticationException('opaque denied'));

    expect($response)->not->toBeNull()
        ->and($response->getStatusCode())->toBe(401)
        ->and($response->getBody()->getContents())->toBe('opaque denied');
});

// ---------------------------------------------------------------------------

function makeOpaqueTokenAuthenticator(
    ?UserProviderInterface $userProvider = null,
    ?OpaqueTokenManagerInterface $opaqueTokenManager = null,
): OpaqueTokenAuthenticator {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider ??= Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $opaqueTokenManager */
    $opaqueTokenManager ??= Mockery::mock(OpaqueTokenManagerInterface::class);

    return new OpaqueTokenAuthenticator(
        userProvider: $userProvider,
        opaqueTokenManager: $opaqueTokenManager,
        successHandler: null,
        failureHandler: null,
    );
}
