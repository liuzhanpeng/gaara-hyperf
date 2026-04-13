<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\EventListener\PasswordExpirationListener;
use GaaraHyperf\EventListener\Priority;
use GaaraHyperf\Exception\PasswordExpiredException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\MemoryUser;
use GaaraHyperf\User\PasswordExpirationAwareUserInterface;
use GaaraHyperf\User\UserInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

afterEach(function (): void {
    Mockery::close();
});

it('subscribes authentication success event with normal priority', function (): void {
    $events = PasswordExpirationListener::getSubscribedEvents();

    expect($events[AuthenticationSuccessEvent::class])->toBe(['onAuthenticationSuccess', Priority::NORMAL]);
});

it('returns when user is not password-expiration-aware', function (): void {
    $listener = new PasswordExpirationListener();
    $event = createPasswordExpirationListenerTestEvent(
        '/home',
        new MemoryUser('alice', 'hashed-password'),
        null
    );

    $listener->onAuthenticationSuccess($event);

    expect(true)->toBeTrue();
});

it('returns when current path is excluded', function (): void {
    $listener = new PasswordExpirationListener(excludedPaths: ['/profile/password']);
    $event = createPasswordExpirationListenerTestEvent(
        '/profile/password',
        new PasswordExpirationListenerTestUser('alice', new DateTimeImmutable('-1 day')),
        null
    );

    $listener->onAuthenticationSuccess($event);

    expect(true)->toBeTrue();
});

it('throws password expired exception when password is expired', function (): void {
    $listener = new PasswordExpirationListener();
    $event = createPasswordExpirationListenerTestEvent(
        '/home',
        new PasswordExpirationListenerTestUser('alice', new DateTimeImmutable('-1 day')),
        null
    );

    expect(fn () => $listener->onAuthenticationSuccess($event))
        ->toThrow(PasswordExpiredException::class, 'Password has expired');
});

it('returns without error when password expires soon and response is null', function (): void {
    $listener = new PasswordExpirationListener(excludedPaths: [], warningDays: 7);
    $event = createPasswordExpirationListenerTestEvent(
        '/home',
        new PasswordExpirationListenerTestUser('alice', new DateTimeImmutable('+2 days')),
        null
    );

    $listener->onAuthenticationSuccess($event);

    expect(true)->toBeTrue();
});

it('returns without error when password is not expiring soon', function (): void {
    $listener = new PasswordExpirationListener(excludedPaths: [], warningDays: 7);
    $event = createPasswordExpirationListenerTestEvent(
        '/home',
        new PasswordExpirationListenerTestUser('alice', new DateTimeImmutable('+20 days')),
        null
    );

    $listener->onAuthenticationSuccess($event);

    expect(true)->toBeTrue();
});

function createPasswordExpirationListenerTestEvent(
    string $path,
    UserInterface $user,
    ?ResponseInterface $response
): AuthenticationSuccessEvent {
    $passport = new Passport('alice', fn () => $user);

    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);

    $request->shouldReceive('getUri')->andReturn($uri);
    $uri->shouldReceive('getPath')->andReturn($path);

    return new AuthenticationSuccessEvent(
        'main',
        $authenticator,
        $token,
        $passport,
        $request,
        $response,
        null
    );
}

class PasswordExpirationListenerTestUser implements UserInterface, PasswordExpirationAwareUserInterface
{
    public function __construct(private string $identifier, private DateTimeInterface $expiresAt)
    {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getExpiresAt(): DateTimeInterface
    {
        return $this->expiresAt;
    }
}
