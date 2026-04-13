<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\X509Authenticator;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('throws exception when ssl client dn header field is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => new X509Authenticator('', 'email', $userProvider, null, null))
        ->toThrow(InvalidArgumentException::class, 'ssl_client_s_dn_field cannot be empty');
});

it('throws exception when identifier field is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => new X509Authenticator('SSL_CLIENT_S_DN', '', $userProvider, null, null))
        ->toThrow(InvalidArgumentException::class, 'identifier_field cannot be empty');
});

it('supports request with openssl style dn and email alias', function (): void {
    $authenticator = createX509AuthenticatorTestAuthenticator();
    $request = createX509AuthenticatorTestRequest('SSL_CLIENT_S_DN', '/C=CN/CN=Alice/emailAddress=alice@example.com');

    expect($authenticator->supports($request))->toBeTrue();
});

it('supports request with rfc style dn and case-insensitive field matching', function (): void {
    $authenticator = createX509AuthenticatorTestAuthenticator(identifierField: 'EMAIL');
    $request = createX509AuthenticatorTestRequest('SSL_CLIENT_S_DN', 'EMAILADDRESS=alice@example.com,CN=Alice,C=CN');

    expect($authenticator->supports($request))->toBeTrue();
});

it('does not support request when identifier cannot be extracted', function (): void {
    $authenticator = createX509AuthenticatorTestAuthenticator(identifierField: 'email');
    $request = createX509AuthenticatorTestRequest('SSL_CLIENT_S_DN', 'C=CN,CN=Alice');

    expect($authenticator->supports($request))->toBeFalse();
});

it('authenticates request and returns passport', function (): void {
    $identifier = 'alice@example.com';
    $user = new class($identifier) implements UserInterface {
        public function __construct(private string $id)
        {
        }

        public function getIdentifier(): string
        {
            return $this->id;
        }
    };

    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    $userProvider->shouldReceive('findByIdentifier')->once()->with($identifier)->andReturn($user);

    $authenticator = new X509Authenticator('SSL_CLIENT_S_DN', 'email', $userProvider, null, null);
    $request = createX509AuthenticatorTestRequest('SSL_CLIENT_S_DN', '/C=CN/CN=Alice/emailAddress=alice@example.com');

    $passport = $authenticator->authenticate($request);

    expect($passport->getUserIdentifier())->toBe($identifier)
        ->and($passport->getUser()->getIdentifier())->toBe($identifier);
});

it('throws invalid credentials when identifier is not found', function (): void {
    $authenticator = createX509AuthenticatorTestAuthenticator();
    $request = createX509AuthenticatorTestRequest('SSL_CLIENT_S_DN', 'C=CN,CN=Alice');

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'User identifier not found in client certificate');
});

it('is non interactive authenticator', function (): void {
    $authenticator = createX509AuthenticatorTestAuthenticator();

    expect($authenticator->isInteractive())->toBeFalse();
});

it('returns 401 response on authentication failure by default', function (): void {
    $authenticator = createX509AuthenticatorTestAuthenticator();
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $response = $authenticator->onAuthenticationFailure('api', $request, new AuthenticationException('x509 denied'));

    expect($response)->not->toBeNull()
        ->and($response->getStatusCode())->toBe(401)
        ->and($response->getBody()->getContents())->toBe('x509 denied');
});

function createX509AuthenticatorTestAuthenticator(
    string $sslClientSDNField = 'SSL_CLIENT_S_DN',
    string $identifierField = 'email',
): X509Authenticator {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    return new X509Authenticator($sslClientSDNField, $identifierField, $userProvider, null, null);
}

function createX509AuthenticatorTestRequest(string $headerField, string $dn): ServerRequestInterface
{
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getHeaderLine')->once()->with($headerField)->andReturn($dn);

    return $request;
}
