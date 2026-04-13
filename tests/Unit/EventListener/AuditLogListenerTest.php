<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\LogoutEvent;
use GaaraHyperf\EventListener\AuditLogListener;
use GaaraHyperf\EventListener\Priority;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\User\MemoryUser;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

afterEach(function (): void {
    Mockery::close();
});

it('subscribes authentication success/failure/logout events with low priority', function (): void {
    $events = AuditLogListener::getSubscribedEvents();

    expect($events[AuthenticationSuccessEvent::class])->toBe(['onAuthenticationSuccess', Priority::LOW])
        ->and($events[AuthenticationFailureEvent::class])->toBe(['onAuthenticationFailure', Priority::LOW])
        ->and($events[LogoutEvent::class])->toBe(['onLogout', Priority::LOW]);
});

it('logs authentication success with expected context', function (): void {
    /** @var LoggerInterface&MockInterface $logger */
    $logger = Mockery::mock(LoggerInterface::class);
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);

    $request = createAuditLogListenerTestRequest('/login', 'Mozilla/5.0');
    $resolver->shouldReceive('resolve')->once()->with($request)->andReturn('127.0.0.1');

    $logger->shouldReceive('log')
        ->once()
        ->with(
            LogLevel::INFO,
            'Authentication success',
            Mockery::on(function (array $context): bool {
                return $context['guard'] === 'main'
                    && str_contains($context['authenticator'], 'AuthenticatorInterface')
                    && $context['user_identifier'] === 'alice'
                    && $context['request_uri'] === '/login'
                    && $context['ip'] === '127.0.0.1'
                    && $context['user_agent'] === 'Mozilla/5.0'
                    && isset($context['occurred_at']);
            })
        );

    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    $token = new AuthenticatedToken('main', 'alice');
    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed-password'));

    $listener = new AuditLogListener($logger, $resolver);
    $listener->onAuthenticationSuccess(new AuthenticationSuccessEvent('main', $authenticator, $token, $passport, $request, null, null));

    expect(true)->toBeTrue();
});

it('logs authentication failure with expected context', function (): void {
    /** @var LoggerInterface&MockInterface $logger */
    $logger = Mockery::mock(LoggerInterface::class);
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);

    $request = createAuditLogListenerTestRequest('/login', 'Mozilla/5.0');
    $resolver->shouldReceive('resolve')->once()->with($request)->andReturn('127.0.0.1');

    $logger->shouldReceive('log')
        ->once()
        ->with(
            LogLevel::ERROR,
            'Authentication failure',
            Mockery::on(function (array $context): bool {
                return $context['guard'] === 'main'
                    && str_contains($context['authenticator'], 'AuthenticatorInterface')
                    && $context['user_identifier'] === 'alice'
                    && $context['request_uri'] === '/login'
                    && $context['ip'] === '127.0.0.1'
                    && $context['user_agent'] === 'Mozilla/5.0'
                    && $context['exception_type'] === AuthenticationException::class
                    && $context['exception_message'] === 'bad credentials'
                    && isset($context['occurred_at']);
            })
        );

    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    $exception = new AuthenticationException('bad credentials', 'alice');

    $listener = new AuditLogListener($logger, $resolver);
    $listener->onAuthenticationFailure(new AuthenticationFailureEvent('main', $authenticator, $exception, null, $request, null));

    expect(true)->toBeTrue();
});

it('logs logout with expected context', function (): void {
    /** @var LoggerInterface&MockInterface $logger */
    $logger = Mockery::mock(LoggerInterface::class);
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);

    $request = createAuditLogListenerTestRequest('/logout', 'Mozilla/5.0');
    $resolver->shouldReceive('resolve')->once()->with($request)->andReturn('127.0.0.1');

    $logger->shouldReceive('log')
        ->once()
        ->with(
            LogLevel::INFO,
            'User logout',
            Mockery::on(function (array $context): bool {
                return $context['guard'] === 'main'
                    && $context['user_identifier'] === 'alice'
                    && $context['request_uri'] === '/logout'
                    && $context['ip'] === '127.0.0.1'
                    && $context['user_agent'] === 'Mozilla/5.0'
                    && isset($context['occurred_at']);
            })
        );

    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    $token->shouldReceive('getGuardName')->once()->andReturn('main');
    $token->shouldReceive('getUserIdentifier')->once()->andReturn('alice');

    $listener = new AuditLogListener($logger, $resolver);
    $listener->onLogout(new LogoutEvent($token, $request));

    expect(true)->toBeTrue();
});

function createAuditLogListenerTestRequest(string $uriText, string $userAgent): ServerRequestInterface
{
    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('__toString')->andReturn($uriText);

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn($userAgent);

    return $request;
}
