<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\EventListener\IPWhiteListListener;
use GaaraHyperf\EventListener\Priority;
use GaaraHyperf\Exception\IPNotInWhiteListException;
use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\IPWhiteListChecker\IPWhiteListCheckerInterface;
use GaaraHyperf\IPWhiteListChecker\IPWhiteListProviderInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\User\MemoryUser;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('subscribes check passport event with high priority', function (): void {
    $events = IPWhiteListListener::getSubscribedEvents();

    expect($events[CheckPassportEvent::class])->toBe(['checkPassport', Priority::HIGH]);
});

it('allows request when ip is in configured array white list', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var IPWhiteListCheckerInterface&MockInterface $checker */
    $checker = Mockery::mock(IPWhiteListCheckerInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('127.0.0.1');
    $checker->shouldReceive('isAllowed')->once()->with('127.0.0.1', ['127.0.0.1'])->andReturn(true);

    $listener = new IPWhiteListListener($resolver, $checker, ['127.0.0.1']);
    $listener->checkPassport(createIPWhiteListListenerTestEvent('alice'));

    expect(true)->toBeTrue();
});

it('allows request when white list provider instance is used', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var IPWhiteListCheckerInterface&MockInterface $checker */
    $checker = Mockery::mock(IPWhiteListCheckerInterface::class);
    $provider = new class implements IPWhiteListProviderInterface {
        public function getWhiteList(): array
        {
            return ['10.0.0.0/8'];
        }
    };

    $resolver->shouldReceive('resolve')->once()->andReturn('10.1.2.3');
    $checker->shouldReceive('isAllowed')->once()->with('10.1.2.3', ['10.0.0.0/8'])->andReturn(true);

    $listener = new IPWhiteListListener($resolver, $checker, $provider);
    $listener->checkPassport(createIPWhiteListListenerTestEvent('alice'));

    expect(true)->toBeTrue();
});

it('allows request when white list provider class string is used', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var IPWhiteListCheckerInterface&MockInterface $checker */
    $checker = Mockery::mock(IPWhiteListCheckerInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('192.168.1.10');
    $checker->shouldReceive('isAllowed')->once()->with('192.168.1.10', ['192.168.0.0/16'])->andReturn(true);

    $listener = new IPWhiteListListener($resolver, $checker, IPWhiteListListenerTestProvider::class);
    $listener->checkPassport(createIPWhiteListListenerTestEvent('alice'));

    expect(true)->toBeTrue();
});

it('throws when provider class string does not exist', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var IPWhiteListCheckerInterface&MockInterface $checker */
    $checker = Mockery::mock(IPWhiteListCheckerInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('127.0.0.1');

    $listener = new IPWhiteListListener($resolver, $checker, 'Not\Existing\Provider');

    expect(fn () => $listener->checkPassport(createIPWhiteListListenerTestEvent('alice')))
        ->toThrow(InvalidArgumentException::class, 'does not exist');
});

it('throws when provider class string does not implement interface', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var IPWhiteListCheckerInterface&MockInterface $checker */
    $checker = Mockery::mock(IPWhiteListCheckerInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('127.0.0.1');

    $listener = new IPWhiteListListener($resolver, $checker, IPWhiteListListenerTestInvalidProvider::class);

    expect(fn () => $listener->checkPassport(createIPWhiteListListenerTestEvent('alice')))
        ->toThrow(InvalidArgumentException::class, 'must implement IPWhiteListProviderInterface');
});

it('throws ip not in white list exception when checker rejects ip', function (): void {
    /** @var IPResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(IPResolverInterface::class);
    /** @var IPWhiteListCheckerInterface&MockInterface $checker */
    $checker = Mockery::mock(IPWhiteListCheckerInterface::class);

    $resolver->shouldReceive('resolve')->once()->andReturn('203.0.113.1');
    $checker->shouldReceive('isAllowed')->once()->with('203.0.113.1', ['127.0.0.1'])->andReturn(false);

    $listener = new IPWhiteListListener($resolver, $checker, ['127.0.0.1']);

    expect(fn () => $listener->checkPassport(createIPWhiteListListenerTestEvent('alice')))
        ->toThrow(IPNotInWhiteListException::class, 'IP address not in white list');
});

function createIPWhiteListListenerTestEvent(string $identifier): CheckPassportEvent
{
    $passport = new Passport($identifier, fn () => new MemoryUser($identifier, 'hashed-password'));

    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    return new CheckPassportEvent('main', $authenticator, $passport, $request);
}

class IPWhiteListListenerTestProvider implements IPWhiteListProviderInterface
{
    public function getWhiteList(): array
    {
        return ['192.168.0.0/16'];
    }
}

class IPWhiteListListenerTestInvalidProvider
{
}
