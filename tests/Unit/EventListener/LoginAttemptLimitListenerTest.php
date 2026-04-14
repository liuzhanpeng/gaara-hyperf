<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\EventListener\LoginAttemptLimitListener;
use GaaraHyperf\EventListener\Priority;
use GaaraHyperf\Exception\TooManyLoginAttemptsException;
use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\RateLimiter\LimitResult;
use GaaraHyperf\RateLimiter\RateLimiterInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\MemoryUser;
use Hyperf\Redis\Redis;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('subscribes check and authentication success events', function (): void {
    $events = LoginAttemptLimitListener::getSubscribedEvents();

    expect($events[CheckPassportEvent::class])->toBe(['checkPassport', Priority::HIGH])
        ->and($events[AuthenticationSuccessEvent::class])->toBe(['onAuthenticationSuccess', Priority::NORMAL]);
});

it('skips check passport when authenticator is non-interactive', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var MockInterface&RateLimiterInterface $rateLimiter */
    $rateLimiter = Mockery::mock(RateLimiterInterface::class);
    $resolver->shouldNotReceive('resolve');
    $rateLimiter->shouldNotReceive('attempt');

    $listener = createLoginAttemptLimitListenerWithRateLimiter($resolver, $rateLimiter);
    $listener->checkPassport(createLoginAttemptLimitCheckEvent(interactive: false, identifier: 'alice', ip: '127.0.0.1'));

    expect(true)->toBeTrue();
});

it('allows interactive request when rate limiter accepts attempt', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var MockInterface&RateLimiterInterface $rateLimiter */
    $rateLimiter = Mockery::mock(RateLimiterInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('127.0.0.1');
    $rateLimiter->shouldReceive('attempt')->once()->with('alice127.0.0.1')->andReturn(new LimitResult(true, 4, 0));

    $listener = createLoginAttemptLimitListenerWithRateLimiter($resolver, $rateLimiter);
    $listener->checkPassport(createLoginAttemptLimitCheckEvent(interactive: true, identifier: 'alice', ip: '127.0.0.1'));

    expect(true)->toBeTrue();
});

it('throws too many login attempts exception when attempt is rejected', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var MockInterface&RateLimiterInterface $rateLimiter */
    $rateLimiter = Mockery::mock(RateLimiterInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('127.0.0.1');
    $rateLimiter->shouldReceive('attempt')->once()->with('alice127.0.0.1')->andReturn(new LimitResult(false, 0, 45));

    $listener = createLoginAttemptLimitListenerWithRateLimiter($resolver, $rateLimiter);

    expect(fn () => $listener->checkPassport(createLoginAttemptLimitCheckEvent(interactive: true, identifier: 'alice', ip: '127.0.0.1')))
        ->toThrow(TooManyLoginAttemptsException::class, 'Too many login attempts');
});

it('checks rate limit without resolving passport user', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var MockInterface&RateLimiterInterface $rateLimiter */
    $rateLimiter = Mockery::mock(RateLimiterInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('127.0.0.1');
    $rateLimiter->shouldReceive('attempt')->once()->with('alice127.0.0.1')->andReturn(new LimitResult(true, 4, 0));

    $listener = createLoginAttemptLimitListenerWithRateLimiter($resolver, $rateLimiter);
    $listener->checkPassport(createLoginAttemptLimitCheckEvent(
        interactive: true,
        identifier: 'alice',
        ip: '127.0.0.1',
        userLoader: fn () => throw new UserNotFoundException('User not found', 'alice'),
    ));

    expect(true)->toBeTrue();
});

it('skips reset on authentication success when authenticator is non-interactive', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var MockInterface&RateLimiterInterface $rateLimiter */
    $rateLimiter = Mockery::mock(RateLimiterInterface::class);
    $resolver->shouldNotReceive('resolve');
    $rateLimiter->shouldNotReceive('reset');

    $listener = createLoginAttemptLimitListenerWithRateLimiter($resolver, $rateLimiter);
    $listener->onAuthenticationSuccess(createLoginAttemptLimitSuccessEvent(interactive: false, identifier: 'alice', ip: '127.0.0.1'));

    expect(true)->toBeTrue();
});

it('resets login attempts on interactive authentication success', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var MockInterface&RateLimiterInterface $rateLimiter */
    $rateLimiter = Mockery::mock(RateLimiterInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('127.0.0.1');
    $rateLimiter->shouldReceive('reset')->once()->with('alice127.0.0.1');

    $listener = createLoginAttemptLimitListenerWithRateLimiter($resolver, $rateLimiter);
    $listener->onAuthenticationSuccess(createLoginAttemptLimitSuccessEvent(interactive: true, identifier: 'alice', ip: '127.0.0.1'));

    expect(true)->toBeTrue();
});

function createLoginAttemptLimitListenerWithRateLimiter(
    IPResolverInterface $resolver,
    RateLimiterInterface $rateLimiter
): LoginAttemptLimitListener {
    /** @var MockInterface&Redis $redis */
    $redis = Mockery::mock(Redis::class);

    $listener = new LoginAttemptLimitListener($redis, $resolver);

    $reflection = new ReflectionProperty(LoginAttemptLimitListener::class, 'rateLimiter');
    $reflection->setAccessible(true);
    $reflection->setValue($listener, $rateLimiter);

    return $listener;
}

function createLoginAttemptLimitCheckEvent(bool $interactive, string $identifier, string $ip, ?callable $userLoader = null): CheckPassportEvent
{
    $passport = new Passport($identifier, $userLoader ?? fn () => new MemoryUser($identifier, 'hashed-password'));

    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $authenticator->shouldReceive('isInteractive')->once()->andReturn($interactive);
    if ($interactive) {
        $request->shouldReceive('getMethod')->zeroOrMoreTimes()->andReturn('POST');
    }

    return new CheckPassportEvent('main', $authenticator, $passport, $request);
}

function createLoginAttemptLimitSuccessEvent(bool $interactive, string $identifier, string $ip): AuthenticationSuccessEvent
{
    $passport = new Passport($identifier, fn () => new MemoryUser($identifier, 'hashed-password'));

    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $authenticator->shouldReceive('isInteractive')->once()->andReturn($interactive);
    if ($interactive) {
        $token->shouldReceive('getUserIdentifier')->once()->andReturn($identifier);
    }

    return new AuthenticationSuccessEvent(
        'main',
        $authenticator,
        $token,
        $passport,
        $request,
        null,
        null
    );
}
