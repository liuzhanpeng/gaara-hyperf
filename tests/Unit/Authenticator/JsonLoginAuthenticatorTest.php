<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticationFailureHandlerInterface;
use GaaraHyperf\Authenticator\JsonLoginAuthenticator;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

afterEach(function (): void {
    Mockery::close();
});

it('throws exception when check path is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => new JsonLoginAuthenticator('', 'username', 'password', 401, 'error', 'Bad credentials', $userProvider, null, null))
        ->toThrow(InvalidArgumentException::class, 'check_path');
});

it('throws exception when username field is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => new JsonLoginAuthenticator('/login-json', '', 'password', 401, 'error', 'Bad credentials', $userProvider, null, null))
        ->toThrow(InvalidArgumentException::class, 'username_field');
});

it('throws exception when password field is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => new JsonLoginAuthenticator('/login-json', 'username', '', 401, 'error', 'Bad credentials', $userProvider, null, null))
        ->toThrow(InvalidArgumentException::class, 'password_field');
});

it('throws exception when error field is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => new JsonLoginAuthenticator('/login-json', 'username', 'password', 401, '', 'Bad credentials', $userProvider, null, null))
        ->toThrow(InvalidArgumentException::class, 'error_field');
});

it('throws exception when error message is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => new JsonLoginAuthenticator('/login-json', 'username', 'password', 401, 'error', '', $userProvider, null, null))
        ->toThrow(InvalidArgumentException::class, 'error_message');
});

it('supports json login request with matching path and method', function (): void {
    $authenticator = createJsonLoginAuthenticatorTestAuthenticator();
    $request = createJsonLoginAuthenticatorTestRequest(
        path: '/login-json',
        method: 'POST',
        contentType: 'application/json; charset=utf-8',
        parsedBody: []
    );

    expect($authenticator->supports($request))->toBeTrue();
});

it('does not support non-json request', function (): void {
    $authenticator = createJsonLoginAuthenticatorTestAuthenticator();
    $request = createJsonLoginAuthenticatorTestRequest(
        path: '/login-json',
        method: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        parsedBody: []
    );

    expect($authenticator->supports($request))->toBeFalse();
});

it('does not support json request when method is not post', function (): void {
    $authenticator = createJsonLoginAuthenticatorTestAuthenticator();
    $request = createJsonLoginAuthenticatorTestRequest(
        path: '/login-json',
        method: 'GET',
        contentType: 'application/json',
        parsedBody: []
    );

    expect($authenticator->supports($request))->toBeFalse();
});

it('does not support json request when path does not match', function (): void {
    $authenticator = createJsonLoginAuthenticatorTestAuthenticator();
    $request = createJsonLoginAuthenticatorTestRequest(
        path: '/other',
        method: 'POST',
        contentType: 'application/json',
        parsedBody: []
    );

    expect($authenticator->supports($request))->toBeFalse();
});

it('authenticates request and attaches password badge', function (): void {
    $username = 'alice';
    $password = 'secret';

    $user = new class($username) implements UserInterface {
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
    $userProvider->shouldReceive('findByIdentifier')->once()->with($username)->andReturn($user);

    $authenticator = createJsonLoginAuthenticatorTestAuthenticator($userProvider);
    $request = createJsonLoginAuthenticatorTestRequest(
        path: '/login-json',
        method: 'POST',
        contentType: 'application/json',
        parsedBody: [
            'username' => $username,
            'password' => $password,
        ]
    );

    $passport = $authenticator->authenticate($request);

    expect($passport->getUserIdentifier())->toBe($username)
        ->and($passport->getBadge(PasswordBadge::class))->toBeInstanceOf(PasswordBadge::class);
});

it('throws exception when username is missing', function (): void {
    $authenticator = createJsonLoginAuthenticatorTestAuthenticator();
    $request = createJsonLoginAuthenticatorTestRequest(
        path: '/login-json',
        method: 'POST',
        contentType: 'application/json',
        parsedBody: [
            'password' => 'secret',
        ]
    );

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'Username is missing');
});

it('throws exception when password is missing', function (): void {
    $authenticator = createJsonLoginAuthenticatorTestAuthenticator();
    $request = createJsonLoginAuthenticatorTestRequest(
        path: '/login-json',
        method: 'POST',
        contentType: 'application/json',
        parsedBody: [
            'username' => 'alice',
        ]
    );

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'Password is missing');
});

it('returns default json failure response for invalid credentials', function (): void {
    $authenticator = createJsonLoginAuthenticatorTestAuthenticator();
    $request = createJsonLoginAuthenticatorTestRequest('/login-json', 'POST', 'application/json', []);

    $response = $authenticator->onAuthenticationFailure(
        'api',
        $request,
        new InvalidCredentialsException('invalid input')
    );

    expect($response)->not->toBeNull();
    expect($response->getStatusCode())->toBe(422);
    expect($response->getHeaderLine('Content-Type'))->toBe('application/json');

    $payload = json_decode($response->getBody()->getContents(), true);
    expect($payload)->toBe(['error' => 'Invalid username or password']);
});

it('returns exception message for non-credential failure', function (): void {
    $authenticator = createJsonLoginAuthenticatorTestAuthenticator();
    $request = createJsonLoginAuthenticatorTestRequest('/login-json', 'POST', 'application/json', []);

    $response = $authenticator->onAuthenticationFailure(
        'api',
        $request,
        new AuthenticationException('unexpected auth error')
    );

    $payload = json_decode($response->getBody()->getContents(), true);
    expect($payload)->toBe(['error' => 'unexpected auth error']);
});

it('uses callable error message when configured', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $authenticator = new JsonLoginAuthenticator(
        checkPath: '/login-json',
        usernameField: 'username',
        passwordField: 'password',
        failureHttpStatusCode: 422,
        errorField: 'error',
        errorMessage: fn (AuthenticationException $exception) => 'mapped:' . $exception->getMessage(),
        userProvider: $userProvider,
        successHandler: null,
        failureHandler: null,
    );

    $request = createJsonLoginAuthenticatorTestRequest('/login-json', 'POST', 'application/json', []);
    $response = $authenticator->onAuthenticationFailure('api', $request, new AuthenticationException('boom'));

    $payload = json_decode($response->getBody()->getContents(), true);
    expect($payload)->toBe(['error' => 'mapped:boom']);
});

it('delegates failure handling to custom failure handler when provided', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var AuthenticationFailureHandlerInterface&MockInterface $failureHandler */
    $failureHandler = Mockery::mock(AuthenticationFailureHandlerInterface::class);
    /** @var MockInterface&ResponseInterface $expectedResponse */
    $expectedResponse = Mockery::mock(ResponseInterface::class);

    $authenticator = new JsonLoginAuthenticator(
        checkPath: '/login-json',
        usernameField: 'username',
        passwordField: 'password',
        failureHttpStatusCode: 422,
        errorField: 'error',
        errorMessage: 'Invalid username or password',
        userProvider: $userProvider,
        successHandler: null,
        failureHandler: $failureHandler,
    );

    $request = createJsonLoginAuthenticatorTestRequest('/login-json', 'POST', 'application/json', []);
    $exception = new InvalidCredentialsException('invalid');

    $failureHandler->shouldReceive('handle')
        ->once()
        ->with('api', $request, $exception, null)
        ->andReturn($expectedResponse);

    expect($authenticator->onAuthenticationFailure('api', $request, $exception))->toBe($expectedResponse);
});

function createJsonLoginAuthenticatorTestAuthenticator(?UserProviderInterface $userProvider = null): JsonLoginAuthenticator
{
    if (is_null($userProvider)) {
        /** @var MockInterface&UserProviderInterface $userProvider */
        $userProvider = Mockery::mock(UserProviderInterface::class);
    }

    return new JsonLoginAuthenticator(
        checkPath: '/login-json',
        usernameField: 'username',
        passwordField: 'password',
        failureHttpStatusCode: 422,
        errorField: 'error',
        errorMessage: 'Invalid username or password',
        userProvider: $userProvider,
        successHandler: null,
        failureHandler: null,
    );
}

function createJsonLoginAuthenticatorTestRequest(
    string $path,
    string $method,
    string $contentType,
    array $parsedBody,
): ServerRequestInterface {
    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('getPath')->andReturn($path);

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturn($contentType);
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn($method);
    $request->shouldReceive('getParsedBody')->andReturn($parsedBody);

    return $request;
}
