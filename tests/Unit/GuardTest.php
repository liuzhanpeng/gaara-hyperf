<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authorization\AccessDeniedHandlerInterface;
use GaaraHyperf\Authorization\AuthorizationCheckerInterface;
use GaaraHyperf\Constants;
use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\Event\LogoutEvent;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\Guard;
use GaaraHyperf\GuardInterface;
use GaaraHyperf\Passport\BadgeInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\RequestMatcher\RequestMatcherInterface;
use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\Token\TokenContextInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\TokenStorage\TokenStorageInterface;
use GaaraHyperf\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use GaaraHyperf\User\MemoryUser;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Mockery\MockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('returns null early when request matches excluded path', function (): void {
    [$guard, $deps] = createGuardTestSubject();

    $deps['tokenStorage']->shouldReceive('get')->once()->with('main')->andReturn(null);
    $deps['tokenContext']->shouldReceive('setToken')->once()->with(null);
    $deps['requestMatcher']->shouldReceive('matchesExcluded')->once()->with($deps['request'])->andReturn(true);

    expect($guard->authenticate($deps['request']))->toBeNull();
});

it('delegates supports check to request matcher', function (): void {
    [$guard, $deps] = createGuardTestSubject();

    $deps['requestMatcher']->shouldReceive('matchesPattern')->once()->with($deps['request'])->andReturn(true);

    expect($guard->supports($deps['request']))->toBeTrue();
});

it('returns configured user provider', function (): void {
    [$guard, $deps] = createGuardTestSubject();

    expect($guard->getUserProvider())->toBe($deps['userProvider']);
});

it('checks authenticated token type via isTokenAuthenticated', function (): void {
    [$guard] = createGuardTestSubject();
    /** @var MockInterface&TokenInterface $plainToken */
    $plainToken = Mockery::mock(TokenInterface::class);

    expect($guard->isTokenAuthenticated(new AuthenticatedToken('main', 'alice')))->toBeTrue()
        ->and($guard->isTokenAuthenticated($plainToken))->toBeFalse()
        ->and($guard->isTokenAuthenticated(null))->toBeFalse();
});

it('delegates isGranted check to authorization checker', function (): void {
    [$guard, $deps] = createGuardTestSubject();
    $token = new AuthenticatedToken('main', 'alice');

    $deps['authorizationChecker']->shouldReceive('check')->once()->with($token, 'ROLE_EDITOR', 'doc:3')->andReturn(true);

    expect($guard->isGranted($token, 'ROLE_EDITOR', 'doc:3'))->toBeTrue();
});

it('returns unauthenticated handler response when token is not authenticated', function (): void {
    [$guard, $deps] = createGuardTestSubject();
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $deps['tokenStorage']->shouldReceive('get')->once()->with('main')->andReturn(null);
    $deps['tokenContext']->shouldReceive('setToken')->once()->with(null);
    $deps['requestMatcher']->shouldReceive('matchesExcluded')->once()->with($deps['request'])->andReturn(false);
    $deps['authenticator']->shouldReceive('supports')->once()->with($deps['request'])->andReturn(false);
    $deps['tokenContext']->shouldReceive('getToken')->once()->andReturn(null);
    $deps['unauthenticatedHandler']->shouldReceive('handle')->once()->with($deps['request'], null)->andReturn($response);

    expect($guard->authenticate($deps['request']))->toBe($response);
});

it('authenticates interactively and persists token before authorization check', function (): void {
    [$guard, $deps] = createGuardTestSubject();

    $token = new AuthenticatedToken('main', 'alice');
    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed'));

    $deps['tokenStorage']->shouldReceive('get')->once()->with('main')->andReturn(null);
    $deps['tokenContext']->shouldReceive('setToken')->once()->with(null);
    $deps['requestMatcher']->shouldReceive('matchesExcluded')->once()->with($deps['request'])->andReturn(false);

    $deps['authenticator']->shouldReceive('supports')->once()->with($deps['request'])->andReturn(true);
    $deps['authenticator']->shouldReceive('authenticate')->once()->with($deps['request'])->andReturn($passport);
    $deps['dispatcher']->shouldReceive('dispatch')->once()->with(Mockery::type(CheckPassportEvent::class))->andReturnUsing(fn ($event) => $event);
    $deps['authenticator']->shouldReceive('createToken')->once()->with($passport, 'main')->andReturn($token);

    $deps['tokenContext']->shouldReceive('getToken')->once()->andReturn(null);
    $deps['authenticator']->shouldReceive('onAuthenticationSuccess')->once()->andReturn(null);
    $deps['dispatcher']->shouldReceive('dispatch')->once()->with(Mockery::type(AuthenticationSuccessEvent::class))->andReturnUsing(fn ($event) => $event);
    $deps['tokenContext']->shouldReceive('setToken')->once()->with($token);
    $deps['authenticator']->shouldReceive('isInteractive')->once()->andReturn(true);
    $deps['tokenStorage']->shouldReceive('set')->once()->with('main', $token);

    $deps['tokenContext']->shouldReceive('getToken')->once()->andReturn($token);
    $deps['requestMatcher']->shouldReceive('matchesLogout')->once()->with($deps['request'])->andReturn(false);
    $deps['request']->shouldReceive('getAttribute')->once()->with(Constants::REQUEST_AUTHORIZATION_ATTRIBUTE, '')->andReturn('ROLE_USER');
    $deps['request']->shouldReceive('getAttribute')->once()->with(Constants::REQUEST_AUTHORIZATION_RESOURCE, null)->andReturn('doc:1');
    $deps['authorizationChecker']->shouldReceive('check')->once()->with($token, 'ROLE_USER', 'doc:1')->andReturn(true);

    expect($guard->authenticate($deps['request']))->toBeNull();
});

it('returns access denied handler response when authorization fails', function (): void {
    [$guard, $deps] = createGuardTestSubject();
    /** @var MockInterface&ResponseInterface $denied */
    $denied = Mockery::mock(ResponseInterface::class);
    $token = new AuthenticatedToken('main', 'alice');

    $deps['tokenStorage']->shouldReceive('get')->once()->with('main')->andReturn($token);
    $deps['tokenContext']->shouldReceive('setToken')->once()->with($token);
    $deps['requestMatcher']->shouldReceive('matchesExcluded')->once()->with($deps['request'])->andReturn(false);
    $deps['authenticator']->shouldReceive('supports')->once()->with($deps['request'])->andReturn(false);
    $deps['tokenContext']->shouldReceive('getToken')->once()->andReturn($token);
    $deps['requestMatcher']->shouldReceive('matchesLogout')->once()->with($deps['request'])->andReturn(false);
    $deps['request']->shouldReceive('getAttribute')->once()->with(Constants::REQUEST_AUTHORIZATION_ATTRIBUTE, '')->andReturn('ROLE_ADMIN');
    $deps['request']->shouldReceive('getAttribute')->once()->with(Constants::REQUEST_AUTHORIZATION_RESOURCE, null)->andReturn('doc:2');
    $deps['authorizationChecker']->shouldReceive('check')->once()->with($token, 'ROLE_ADMIN', 'doc:2')->andReturn(false);
    $deps['accessDeniedHandler']->shouldReceive('handle')->once()->with($deps['request'], $token, 'ROLE_ADMIN', 'doc:2')->andReturn($denied);

    expect($guard->authenticate($deps['request']))->toBe($denied);
});

it('processes logout request and clears token context and storage', function (): void {
    [$guard, $deps] = createGuardTestSubject();
    /** @var MockInterface&ResponseInterface $logoutResponse */
    $logoutResponse = Mockery::mock(ResponseInterface::class);
    $token = new AuthenticatedToken('main', 'alice');

    $deps['tokenStorage']->shouldReceive('get')->once()->with('main')->andReturn($token);
    $deps['tokenContext']->shouldReceive('setToken')->once()->with($token);
    $deps['requestMatcher']->shouldReceive('matchesExcluded')->once()->with($deps['request'])->andReturn(false);
    $deps['authenticator']->shouldReceive('supports')->once()->with($deps['request'])->andReturn(false);
    $deps['tokenContext']->shouldReceive('getToken')->once()->andReturn($token);
    $deps['requestMatcher']->shouldReceive('matchesLogout')->once()->with($deps['request'])->andReturn(true);
    $deps['dispatcher']->shouldReceive('dispatch')->once()->with(Mockery::type(LogoutEvent::class))->andReturnUsing(function ($event) use ($logoutResponse) {
        $event->setResponse($logoutResponse);
        return $event;
    });
    $deps['tokenStorage']->shouldReceive('delete')->once()->with('main');
    $deps['tokenContext']->shouldReceive('setToken')->once()->with(null);

    expect($guard->authenticate($deps['request']))->toBe($logoutResponse);
});

it('returns null on logout when no token exists', function (): void {
    [$guard, $deps] = createGuardTestSubject();

    $deps['tokenContext']->shouldReceive('getToken')->once()->andReturn(null);
    $deps['dispatcher']->shouldNotReceive('dispatch');
    $deps['tokenStorage']->shouldNotReceive('delete');
    $deps['tokenContext']->shouldNotReceive('setToken');

    expect($guard->logout())->toBeNull();
});

it('throws when authenticating user without configured authenticators', function (): void {
    /** @var MockInterface&RequestMatcherInterface $requestMatcher */
    $requestMatcher = Mockery::mock(RequestMatcherInterface::class);
    /** @var MockInterface&TokenStorageInterface $tokenStorage */
    $tokenStorage = Mockery::mock(TokenStorageInterface::class);
    /** @var MockInterface&TokenContextInterface $tokenContext */
    $tokenContext = Mockery::mock(TokenContextInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&UnauthenticatedHandlerInterface $unauthenticatedHandler */
    $unauthenticatedHandler = Mockery::mock(UnauthenticatedHandlerInterface::class);
    /** @var AuthorizationCheckerInterface&MockInterface $authorizationChecker */
    $authorizationChecker = Mockery::mock(AuthorizationCheckerInterface::class);
    /** @var AccessDeniedHandlerInterface&MockInterface $accessDeniedHandler */
    $accessDeniedHandler = Mockery::mock(AccessDeniedHandlerInterface::class);
    /** @var EventDispatcherInterface&MockInterface $dispatcher */
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&UserInterface $user */
    $user = Mockery::mock(UserInterface::class);

    $user->shouldReceive('getIdentifier')->once()->andReturn('alice');

    $guard = new Guard(
        name: 'main',
        requestMatcher: $requestMatcher,
        tokenStorage: $tokenStorage,
        tokenContext: $tokenContext,
        userProvider: $userProvider,
        authenticators: [],
        unauthenticatedHandler: $unauthenticatedHandler,
        authorizationChecker: $authorizationChecker,
        accessDeniedHandler: $accessDeniedHandler,
        eventDispatcher: $dispatcher,
    );

    expect(fn () => $guard->authenticateUser($user, $request))
        ->toThrow(RuntimeException::class, 'No authenticator configured for guard main');
});

it('throws when specified authenticator does not exist for authenticateUser', function (): void {
    [$guard] = createGuardTestSubject();
    $user = new MemoryUser('alice', 'hashed');
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    expect(fn () => $guard->authenticateUser($user, $request, 'missing'))
        ->toThrow(RuntimeException::class, 'Authenticator "missing" not found for guard main');
});

it('returns failure response when unresolved badge triggers credential not resolved', function (): void {
    [$guard, $deps] = createGuardTestSubject();
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $user = new MemoryUser('alice', 'hashed');
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $badge = new class implements BadgeInterface {
        public function isResolved(): bool
        {
            return false;
        }
    };

    $deps['dispatcher']->shouldReceive('dispatch')->once()->with(Mockery::type(CheckPassportEvent::class))->andReturnUsing(fn ($event) => $event);
    $deps['authenticator']->shouldReceive('onAuthenticationFailure')
        ->once()
        ->with('main', $request, Mockery::on(function (AuthenticationException $exception): bool {
            return $exception->getMessage() === 'Credential not resolved' && $exception->getUserIdentifier() === 'alice';
        }), Mockery::type(Passport::class))
        ->andReturn($response);
    $deps['dispatcher']->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(AuthenticationFailureEvent::class))
        ->andReturnUsing(fn ($event) => $event);

    expect($guard->authenticateUser($user, $request, 'api', [$badge]))->toBe($response);
});

it('uses response from authentication failure event when authenticator throws exception', function (): void {
    [$guard, $deps] = createGuardTestSubject();
    /** @var MockInterface&ResponseInterface $responseFromAuthenticator */
    $responseFromAuthenticator = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&ResponseInterface $responseFromEvent */
    $responseFromEvent = Mockery::mock(ResponseInterface::class);

    $deps['tokenStorage']->shouldReceive('get')->once()->with('main')->andReturn(null);
    $deps['tokenContext']->shouldReceive('setToken')->once()->with(null);
    $deps['requestMatcher']->shouldReceive('matchesExcluded')->once()->with($deps['request'])->andReturn(false);
    $deps['authenticator']->shouldReceive('supports')->once()->with($deps['request'])->andReturn(true);
    $deps['authenticator']->shouldReceive('authenticate')->once()->with($deps['request'])->andThrow(new AuthenticationException('bad credentials', 'alice'));
    $deps['authenticator']->shouldReceive('onAuthenticationFailure')
        ->once()
        ->with('main', $deps['request'], Mockery::type(AuthenticationException::class), null)
        ->andReturn($responseFromAuthenticator);
    $deps['dispatcher']->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(AuthenticationFailureEvent::class))
        ->andReturnUsing(function (AuthenticationFailureEvent $event) use ($responseFromEvent): AuthenticationFailureEvent {
            $event->setResponse($responseFromEvent);
            return $event;
        });

    expect($guard->authenticate($deps['request']))->toBe($responseFromEvent);
});

it('dispatches check passport event before failing when passport user cannot be resolved', function (): void {
    [$guard, $deps] = createGuardTestSubject();
    /** @var MockInterface&ResponseInterface $responseFromAuthenticator */
    $responseFromAuthenticator = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&ResponseInterface $responseFromEvent */
    $responseFromEvent = Mockery::mock(ResponseInterface::class);

    $passport = new Passport('alice', fn () => throw new UserNotFoundException('User not found', 'alice'));

    $deps['tokenStorage']->shouldReceive('get')->once()->with('main')->andReturn(null);
    $deps['tokenContext']->shouldReceive('setToken')->once()->with(null);
    $deps['requestMatcher']->shouldReceive('matchesExcluded')->once()->with($deps['request'])->andReturn(false);
    $deps['authenticator']->shouldReceive('supports')->once()->with($deps['request'])->andReturn(true);
    $deps['authenticator']->shouldReceive('authenticate')->once()->with($deps['request'])->andReturn($passport);
    $deps['dispatcher']->shouldReceive('dispatch')->once()->with(Mockery::type(CheckPassportEvent::class))->andReturnUsing(fn ($event) => $event);
    $deps['authenticator']->shouldReceive('createToken')->never();
    $deps['authenticator']->shouldReceive('onAuthenticationFailure')
        ->once()
        ->with('main', $deps['request'], Mockery::on(function (AuthenticationException $exception): bool {
            return $exception instanceof UserNotFoundException
                && $exception->getMessage() === 'User not found'
                && $exception->getUserIdentifier() === 'alice';
        }), $passport)
        ->andReturn($responseFromAuthenticator);
    $deps['dispatcher']->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(AuthenticationFailureEvent::class))
        ->andReturnUsing(function (AuthenticationFailureEvent $event) use ($responseFromEvent): AuthenticationFailureEvent {
            $event->setResponse($responseFromEvent);
            return $event;
        });

    expect($guard->authenticate($deps['request']))->toBe($responseFromEvent);
});

/**
 * @return array{0: GuardInterface, 1: array<string, mixed>}
 */
function createGuardTestSubject(): array
{
    /** @var MockInterface&RequestMatcherInterface $requestMatcher */
    $requestMatcher = Mockery::mock(RequestMatcherInterface::class);
    /** @var MockInterface&TokenStorageInterface $tokenStorage */
    $tokenStorage = Mockery::mock(TokenStorageInterface::class);
    /** @var MockInterface&TokenContextInterface $tokenContext */
    $tokenContext = Mockery::mock(TokenContextInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var MockInterface&UnauthenticatedHandlerInterface $unauthenticatedHandler */
    $unauthenticatedHandler = Mockery::mock(UnauthenticatedHandlerInterface::class);
    /** @var AuthorizationCheckerInterface&MockInterface $authorizationChecker */
    $authorizationChecker = Mockery::mock(AuthorizationCheckerInterface::class);
    /** @var AccessDeniedHandlerInterface&MockInterface $accessDeniedHandler */
    $accessDeniedHandler = Mockery::mock(AccessDeniedHandlerInterface::class);
    /** @var EventDispatcherInterface&MockInterface $dispatcher */
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $guard = new Guard(
        name: 'main',
        requestMatcher: $requestMatcher,
        tokenStorage: $tokenStorage,
        tokenContext: $tokenContext,
        userProvider: $userProvider,
        authenticators: ['api' => $authenticator],
        unauthenticatedHandler: $unauthenticatedHandler,
        authorizationChecker: $authorizationChecker,
        accessDeniedHandler: $accessDeniedHandler,
        eventDispatcher: $dispatcher,
    );

    return [$guard, [
        'requestMatcher' => $requestMatcher,
        'tokenStorage' => $tokenStorage,
        'tokenContext' => $tokenContext,
        'userProvider' => $userProvider,
        'unauthenticatedHandler' => $unauthenticatedHandler,
        'authorizationChecker' => $authorizationChecker,
        'accessDeniedHandler' => $accessDeniedHandler,
        'dispatcher' => $dispatcher,
        'authenticator' => $authenticator,
        'request' => $request,
    ]];
}
