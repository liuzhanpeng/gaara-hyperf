<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticationFailureHandlerInterface;
use GaaraHyperf\Authenticator\AuthenticationSuccessHandlerInterface;
use GaaraHyperf\Authenticator\FormLoginAuthenticator;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Passport\CsrfTokenBadge;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
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

it('throws exception when check path is empty', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);

    expect(fn () => new FormLoginAuthenticator(
        checkPath: '',
        targetPath: '/home',
        failurePath: '/login',
        usernameField: 'username',
        passwordField: 'password',
        redirectEnabled: true,
        redirectField: '_target_path',
        csrfEnabled: true,
        csrfField: '_csrf_token',
        csrfId: 'login',
        errorMessage: 'Auth failed',
        userProvider: $userProvider,
        response: $response,
        session: $session,
        successHandler: null,
        failureHandler: null,
    ))->toThrow(InvalidArgumentException::class, 'check_path');
});

it('supports form login request with matching path and post method', function (): void {
    $authenticator = createFormLoginAuthenticatorTestAuthenticator();
    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', []);

    expect($authenticator->supports($request))->toBeTrue();
});

it('does not support request when path mismatches', function (): void {
    $authenticator = createFormLoginAuthenticatorTestAuthenticator();
    $request = createFormLoginAuthenticatorTestRequest('/other', 'POST', []);

    expect($authenticator->supports($request))->toBeFalse();
});

it('does not support request when method is not post', function (): void {
    $authenticator = createFormLoginAuthenticatorTestAuthenticator();
    $request = createFormLoginAuthenticatorTestRequest('/login', 'GET', []);

    expect($authenticator->supports($request))->toBeFalse();
});

it('authenticates form request and adds password and csrf badges', function (): void {
    $username = 'alice';
    $password = 'secret';
    $csrfToken = 'csrf-123';

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

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(userProvider: $userProvider, csrfEnabled: true);
    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', [
        'username' => $username,
        'password' => $password,
        '_csrf_token' => $csrfToken,
    ]);

    $passport = $authenticator->authenticate($request);

    expect($passport->getUserIdentifier())->toBe($username)
        ->and($passport->getBadge(PasswordBadge::class))->toBeInstanceOf(PasswordBadge::class)
        ->and($passport->getBadge(CsrfTokenBadge::class))->toBeInstanceOf(CsrfTokenBadge::class);
});

it('throws exception when csrf is enabled and csrf token is missing', function (): void {
    $authenticator = createFormLoginAuthenticatorTestAuthenticator(csrfEnabled: true);
    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', [
        'username' => 'alice',
        'password' => 'secret',
    ]);

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'CSRF token is missing');
});

it('throws exception when username is missing', function (): void {
    $authenticator = createFormLoginAuthenticatorTestAuthenticator(csrfEnabled: false);
    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', [
        'password' => 'secret',
    ]);

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'Username is missing');
});

it('throws exception when password is missing', function (): void {
    $authenticator = createFormLoginAuthenticatorTestAuthenticator(csrfEnabled: false);
    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', [
        'username' => 'alice',
    ]);

    expect(fn () => $authenticator->authenticate($request))
        ->toThrow(InvalidCredentialsException::class, 'Password is missing');
});

it('authenticates without csrf badge when csrf check is disabled', function (): void {
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

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(userProvider: $userProvider, csrfEnabled: false);
    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', [
        'username' => $username,
        'password' => $password,
    ]);

    $passport = $authenticator->authenticate($request);

    expect($passport->getBadge(PasswordBadge::class))->toBeInstanceOf(PasswordBadge::class)
        ->and($passport->getBadge(CsrfTokenBadge::class))->toBeNull();
});

it('redirects to target path on authentication success by default', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $redirectResponse */
    $redirectResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);

    $session->shouldReceive('migrate')->once()->with(true);
    $response->shouldReceive('redirect')->once()->with('/home')->andReturn($redirectResponse);

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(response: $response, session: $session);

    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', []);
    $token = Mockery::mock(TokenInterface::class);
    $passport = createFormLoginAuthenticatorTestPassport('alice');

    expect($authenticator->onAuthenticationSuccess('web', $request, $token, $passport))->toBe($redirectResponse);
});

it('redirects to request target field when enabled', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $redirectResponse */
    $redirectResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);

    $session->shouldReceive('migrate')->once()->with(true);
    $response->shouldReceive('redirect')->once()->with('/dashboard')->andReturn($redirectResponse);

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(
        response: $response,
        session: $session,
        redirectEnabled: true,
    );

    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', [
        '_target_path' => urlencode('/dashboard'),
    ]);

    $token = Mockery::mock(TokenInterface::class);
    $passport = createFormLoginAuthenticatorTestPassport('alice');

    expect($authenticator->onAuthenticationSuccess('web', $request, $token, $passport))->toBe($redirectResponse);
});

it('delegates success handling to custom success handler when configured', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);
    /** @var AuthenticationSuccessHandlerInterface&MockInterface $successHandler */
    $successHandler = Mockery::mock(AuthenticationSuccessHandlerInterface::class);
    /** @var MockInterface&ResponseInterface $expectedResponse */
    $expectedResponse = Mockery::mock(ResponseInterface::class);

    $session->shouldReceive('migrate')->once()->with(true);
    $response->shouldReceive('redirect')->never();

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(
        response: $response,
        session: $session,
        successHandler: $successHandler,
    );

    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', []);
    $token = Mockery::mock(TokenInterface::class);
    $passport = createFormLoginAuthenticatorTestPassport('alice');

    $successHandler->shouldReceive('handle')
        ->once()
        ->with('web', $request, $token, $passport)
        ->andReturn($expectedResponse);

    expect($authenticator->onAuthenticationSuccess('web', $request, $token, $passport))->toBe($expectedResponse);
});

it('flashes error and redirects to failure path on authentication failure', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $redirectResponse */
    $redirectResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);

    $session->shouldReceive('flash')->once()->with('authentication_error', 'Auth failed');
    $response->shouldReceive('redirect')->once()->with('/login')->andReturn($redirectResponse);

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(response: $response, session: $session);
    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', []);
    $exception = new InvalidCredentialsException('invalid');

    expect($authenticator->onAuthenticationFailure('web', $request, $exception))->toBe($redirectResponse);
});

it('uses callable error message when flashing authentication error', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $redirectResponse */
    $redirectResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);

    $session->shouldReceive('flash')->once()->with('authentication_error', 'mapped:invalid');
    $response->shouldReceive('redirect')->once()->with('/login')->andReturn($redirectResponse);

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(
        response: $response,
        session: $session,
        errorMessage: fn (InvalidCredentialsException $exception) => 'mapped:' . $exception->getMessage(),
    );

    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', []);
    $exception = new InvalidCredentialsException('invalid');

    expect($authenticator->onAuthenticationFailure('web', $request, $exception))->toBe($redirectResponse);
});

it('does not flash when session is not Hyperf Session instance', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $redirectResponse */
    $redirectResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);

    $response->shouldReceive('redirect')->once()->with('/login')->andReturn($redirectResponse);

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(response: $response, session: $session);
    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', []);
    $exception = new InvalidCredentialsException('invalid');

    expect($authenticator->onAuthenticationFailure('web', $request, $exception))->toBe($redirectResponse);
});

it('delegates failure handling to custom failure handler when configured', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&Session $session */
    $session = Mockery::mock(Session::class);
    /** @var AuthenticationFailureHandlerInterface&MockInterface $failureHandler */
    $failureHandler = Mockery::mock(AuthenticationFailureHandlerInterface::class);
    /** @var MockInterface&ResponseInterface $expectedResponse */
    $expectedResponse = Mockery::mock(ResponseInterface::class);

    $authenticator = createFormLoginAuthenticatorTestAuthenticator(
        response: $response,
        session: $session,
        failureHandler: $failureHandler,
    );

    $request = createFormLoginAuthenticatorTestRequest('/login', 'POST', []);
    $exception = new InvalidCredentialsException('invalid');

    $failureHandler->shouldReceive('handle')
        ->once()
        ->with('web', $request, $exception, null)
        ->andReturn($expectedResponse);

    expect($authenticator->onAuthenticationFailure('web', $request, $exception))->toBe($expectedResponse);
});

function createFormLoginAuthenticatorTestAuthenticator(
    ?UserProviderInterface $userProvider = null,
    ?HyperfResponseInterface $response = null,
    ?SessionInterface $session = null,
    bool $csrfEnabled = true,
    bool $redirectEnabled = true,
    ?AuthenticationSuccessHandlerInterface $successHandler = null,
    ?AuthenticationFailureHandlerInterface $failureHandler = null,
    Closure|string $errorMessage = 'Auth failed',
): FormLoginAuthenticator {
    if (is_null($userProvider)) {
        /** @var MockInterface&UserProviderInterface $userProvider */
        $userProvider = Mockery::mock(UserProviderInterface::class);
    }

    if (is_null($response)) {
        /** @var HyperfResponseInterface&MockInterface $response */
        $response = Mockery::mock(HyperfResponseInterface::class);
    }

    if (is_null($session)) {
        /** @var MockInterface&SessionInterface $session */
        $session = Mockery::mock(Session::class);
    }

    return new FormLoginAuthenticator(
        checkPath: '/login',
        targetPath: '/home',
        failurePath: '/login',
        usernameField: 'username',
        passwordField: 'password',
        redirectEnabled: $redirectEnabled,
        redirectField: '_target_path',
        csrfEnabled: $csrfEnabled,
        csrfField: '_csrf_token',
        csrfId: 'login',
        errorMessage: $errorMessage,
        userProvider: $userProvider,
        response: $response,
        session: $session,
        successHandler: $successHandler,
        failureHandler: $failureHandler,
    );
}

function createFormLoginAuthenticatorTestRequest(string $path, string $method, array $parsedBody): ServerRequestInterface
{
    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('getPath')->andReturn($path);

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn($method);
    $request->shouldReceive('getParsedBody')->andReturn($parsedBody);

    return $request;
}

function createFormLoginAuthenticatorTestPassport(string $identifier): Passport
{
    $user = new class($identifier) implements UserInterface {
        public function __construct(private string $id)
        {
        }

        public function getIdentifier(): string
        {
            return $this->id;
        }
    };

    return new Passport($identifier, fn () => $user);
}
