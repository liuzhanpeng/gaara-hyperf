<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\CsrfTokenManager\CsrfToken;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\EventListener\CsrfTokenBadgeCheckListener;
use GaaraHyperf\EventListener\Priority;
use GaaraHyperf\Exception\InvalidCsrfTokenException;
use GaaraHyperf\Passport\CsrfTokenBadge;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\User\MemoryUser;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('subscribes check passport event with normal priority', function (): void {
    $events = CsrfTokenBadgeCheckListener::getSubscribedEvents();

    expect($events[CheckPassportEvent::class])->toBe(['checkPassport', Priority::NORMAL]);
});

it('returns when csrf badge is absent', function (): void {
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);
    $manager->shouldNotReceive('verify');

    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed'));

    $listener = new CsrfTokenBadgeCheckListener($manager);
    $listener->checkPassport(createCsrfTokenBadgeCheckListenerTestEvent($passport));

    expect(true)->toBeTrue();
});

it('returns when csrf badge is already resolved', function (): void {
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);
    $manager->shouldNotReceive('verify');

    $badge = new CsrfTokenBadge('authenticate', 'csrf-token');
    $badge->resolve();
    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed'), [$badge]);

    $listener = new CsrfTokenBadgeCheckListener($manager);
    $listener->checkPassport(createCsrfTokenBadgeCheckListenerTestEvent($passport));

    expect($badge->isResolved())->toBeTrue();
});

it('throws invalid csrf token exception when verification fails', function (): void {
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);
    $manager->shouldReceive('verify')
        ->once()
        ->with(Mockery::on(fn (CsrfToken $token) => $token->getId() === 'authenticate' && $token->getValue() === 'csrf-token'))
        ->andReturn(false);

    $badge = new CsrfTokenBadge('authenticate', 'csrf-token');
    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed'), [$badge]);

    $listener = new CsrfTokenBadgeCheckListener($manager);

    expect(fn () => $listener->checkPassport(createCsrfTokenBadgeCheckListenerTestEvent($passport)))
        ->toThrow(InvalidCsrfTokenException::class, 'Invalid CSRF token');
});

it('resolves csrf badge when verification succeeds', function (): void {
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);
    $manager->shouldReceive('verify')
        ->once()
        ->with(Mockery::on(fn (CsrfToken $token) => $token->getId() === 'authenticate' && $token->getValue() === 'csrf-token'))
        ->andReturn(true);

    $badge = new CsrfTokenBadge('authenticate', 'csrf-token');
    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed'), [$badge]);

    $listener = new CsrfTokenBadgeCheckListener($manager);
    $listener->checkPassport(createCsrfTokenBadgeCheckListenerTestEvent($passport));

    expect($badge->isResolved())->toBeTrue();
});

function createCsrfTokenBadgeCheckListenerTestEvent(Passport $passport): CheckPassportEvent
{
    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    return new CheckPassportEvent('main', $authenticator, $passport, $request);
}
