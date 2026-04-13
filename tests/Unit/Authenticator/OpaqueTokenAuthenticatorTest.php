<?php

declare(strict_types=1);

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\Authenticator\OpaqueTokenAuthenticator;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('supports request when access token can be extracted', function (): void {
    $request = Mockery::mock(ServerRequestInterface::class);
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    $extractor->shouldReceive('extract')->once()->with($request)->andReturn('token-1');

    $authenticator = createOpaqueTokenAuthenticatorTestAuthenticator(accessTokenExtractor: $extractor);

    expect($authenticator->supports($request))->toBeTrue();
});

it('does not support request when access token is missing', function (): void {
    $request = Mockery::mock(ServerRequestInterface::class);
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    $extractor->shouldReceive('extract')->once()->with($request)->andReturnNull();

    $authenticator = createOpaqueTokenAuthenticatorTestAuthenticator(accessTokenExtractor: $extractor);

    expect($authenticator->supports($request))->toBeFalse();
});

it('throws exception when access token is missing during authenticate', function (): void {
    $request = Mockery::mock(ServerRequestInterface::class);
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    $extractor->shouldReceive('extract')->once()->with($request)->andReturnNull();

    $authenticator = createOpaqueTokenAuthenticatorTestAuthenticator(accessTokenExtractor: $extractor);

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'Access token is missing');
});

it('throws exception when access token is invalid', function (): void {
    $request = Mockery::mock(ServerRequestInterface::class);
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);

    $extractor->shouldReceive('extract')->once()->with($request)->andReturn('token-2');
    $manager->shouldReceive('resolve')->once()->with('token-2')->andReturnNull();

    $authenticator = createOpaqueTokenAuthenticatorTestAuthenticator(
        opaqueTokenManager: $manager,
        accessTokenExtractor: $extractor,
    );

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'Invalid access token');
});

it('authenticates request with valid opaque token', function (): void {
    $request = Mockery::mock(ServerRequestInterface::class);
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);

    $user = new class('user-1') implements UserInterface {
        public function __construct(private string $identifier)
        {
        }

        public function getIdentifier(): string
        {
            return $this->identifier;
        }
    };

    $extractor->shouldReceive('extract')->once()->with($request)->andReturn('token-3');
    $manager->shouldReceive('resolve')->once()->with('token-3')->andReturn(new AuthenticatedToken('api', 'user-1'));

    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    $userProvider->shouldReceive('findByIdentifier')->once()->with('user-1')->andReturn($user);

    $authenticator = createOpaqueTokenAuthenticatorTestAuthenticator(
        userProvider: $userProvider,
        opaqueTokenManager: $manager,
        accessTokenExtractor: $extractor,
    );

    $passport = $authenticator->authenticate($request);

    expect($passport->getUserIdentifier())->toBe('user-1')
        ->and($passport->getUser()->getIdentifier())->toBe('user-1');
});

it('is non interactive authenticator', function (): void {
    $authenticator = createOpaqueTokenAuthenticatorTestAuthenticator();

    expect($authenticator->isInteractive())->toBeFalse();
});

it('returns 401 response on authentication failure by default', function (): void {
    $authenticator = createOpaqueTokenAuthenticatorTestAuthenticator();
    $request = Mockery::mock(ServerRequestInterface::class);

    $response = $authenticator->onAuthenticationFailure('api', $request, new AuthenticationException('opaque denied'));

    expect($response)->not->toBeNull()
        ->and($response->getStatusCode())->toBe(401)
        ->and($response->getBody()->getContents())->toBe('opaque denied');
});

function createOpaqueTokenAuthenticatorTestAuthenticator(
    ?UserProviderInterface $userProvider = null,
    ?OpaqueTokenManagerInterface $opaqueTokenManager = null,
    ?AccessTokenExtractorInterface $accessTokenExtractor = null,
): OpaqueTokenAuthenticator {
    if (is_null($userProvider)) {
        /** @var MockInterface&UserProviderInterface $userProvider */
        $userProvider = Mockery::mock(UserProviderInterface::class);
    }

    if (is_null($opaqueTokenManager)) {
        /** @var MockInterface&OpaqueTokenManagerInterface $opaqueTokenManager */
        $opaqueTokenManager = Mockery::mock(OpaqueTokenManagerInterface::class);
    }

    if (is_null($accessTokenExtractor)) {
        /** @var AccessTokenExtractorInterface&MockInterface $accessTokenExtractor */
        $accessTokenExtractor = Mockery::mock(AccessTokenExtractorInterface::class);
    }

    return new OpaqueTokenAuthenticator(
        userProvider: $userProvider,
        opaqueTokenManager: $opaqueTokenManager,
        accessTokenExtractor: $accessTokenExtractor,
        successHandler: null,
        failureHandler: null,
    );
}
