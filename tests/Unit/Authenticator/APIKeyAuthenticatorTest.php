<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\APIKeyAuthenticator;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('throws exception when api key field is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => new APIKeyAuthenticator('', $userProvider, null, null))
        ->toThrow(InvalidArgumentException::class, 'apiKeyField cannot be empty');
});

it('supports request when api key header exists', function (): void {
    $authenticator = createApiKeyAuthenticatorTestAuthenticator();
    $request = createApiKeyAuthenticatorTestRequest('test-key');

    expect($authenticator->supports($request))->toBeTrue();
});

it('does not support request when api key header is missing', function (): void {
    $authenticator = createApiKeyAuthenticatorTestAuthenticator();
    $request = createApiKeyAuthenticatorTestRequest('');

    expect($authenticator->supports($request))->toBeFalse();
});

it('authenticates request and returns passport', function (): void {
    $apiKey = 'client-key';
    $user = new class($apiKey) implements UserInterface {
        public function __construct(private string $identifier)
        {
        }

        public function getIdentifier(): string
        {
            return $this->identifier;
        }
    };

    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    $userProvider->shouldReceive('findByIdentifier')->once()->with($apiKey)->andReturn($user);

    $authenticator = new APIKeyAuthenticator('X-API-KEY', $userProvider, null, null);
    $request = createApiKeyAuthenticatorTestRequest($apiKey);

    $passport = $authenticator->authenticate($request);

    expect($passport->getUserIdentifier())->toBe($apiKey)
        ->and($passport->getUser()->getIdentifier())->toBe($apiKey);
});

it('throws invalid credentials exception when api key is missing in authenticate', function (): void {
    $authenticator = createApiKeyAuthenticatorTestAuthenticator();
    $request = createApiKeyAuthenticatorTestRequest('');

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'API key is missing');
});

it('is non interactive authenticator', function (): void {
    $authenticator = createApiKeyAuthenticatorTestAuthenticator();

    expect($authenticator->isInteractive())->toBeFalse();
});

it('returns 401 response with exception message on authentication failure by default', function (): void {
    $authenticator = createApiKeyAuthenticatorTestAuthenticator();
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $response = $authenticator->onAuthenticationFailure('api', $request, new AuthenticationException('denied'));

    expect($response)->not->toBeNull()
        ->and($response->getStatusCode())->toBe(401)
        ->and($response->getBody()->getContents())->toBe('denied');
});

function createApiKeyAuthenticatorTestAuthenticator(): APIKeyAuthenticator
{
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    return new APIKeyAuthenticator('X-API-KEY', $userProvider, null, null);
}

function createApiKeyAuthenticatorTestRequest(string $apiKey): ServerRequestInterface
{
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getHeaderLine')->once()->with('X-API-KEY')->andReturn($apiKey);

    return $request;
}
