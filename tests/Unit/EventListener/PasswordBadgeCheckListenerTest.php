<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\EventListener\PasswordBadgeCheckListener;
use GaaraHyperf\EventListener\Priority;
use GaaraHyperf\Exception\InvalidPasswordException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\PasswordHasher\PasswordHasherInterface;
use GaaraHyperf\User\MemoryUser;
use GaaraHyperf\User\UserInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('subscribes check passport event with normal priority', function (): void {
    $events = PasswordBadgeCheckListener::getSubscribedEvents();

    expect($events[CheckPassportEvent::class])->toBe(['checkPassport', Priority::NORMAL]);
});

it('returns when password badge is absent', function (): void {
    /** @var MockInterface&PasswordHasherInterface $hasher */
    $hasher = Mockery::mock(PasswordHasherInterface::class);
    $hasher->shouldNotReceive('verify');

    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed'));

    $listener = new PasswordBadgeCheckListener($hasher);
    $listener->checkPassport(createPasswordBadgeCheckListenerTestEvent($passport));

    expect(true)->toBeTrue();
});

it('returns when password badge is already resolved', function (): void {
    /** @var MockInterface&PasswordHasherInterface $hasher */
    $hasher = Mockery::mock(PasswordHasherInterface::class);
    $hasher->shouldNotReceive('verify');

    $badge = new PasswordBadge('plain-password');
    $badge->resolve();
    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed'), [$badge]);

    $listener = new PasswordBadgeCheckListener($hasher);
    $listener->checkPassport(createPasswordBadgeCheckListenerTestEvent($passport));

    expect($badge->isResolved())->toBeTrue();
});

it('throws when user does not implement password aware interface', function (): void {
    /** @var MockInterface&PasswordHasherInterface $hasher */
    $hasher = Mockery::mock(PasswordHasherInterface::class);

    $passport = new Passport(
        'alice',
        fn () => new class implements UserInterface {
            public function getIdentifier(): string
            {
                return 'alice';
            }
        },
        [new PasswordBadge('plain-password')]
    );

    $listener = new PasswordBadgeCheckListener($hasher);

    expect(fn () => $listener->checkPassport(createPasswordBadgeCheckListenerTestEvent($passport)))
        ->toThrow(RuntimeException::class, 'must implement PasswordAwareUserInterface');
});

it('throws invalid password exception when verification fails', function (): void {
    /** @var MockInterface&PasswordHasherInterface $hasher */
    $hasher = Mockery::mock(PasswordHasherInterface::class);
    $hasher->shouldReceive('verify')->once()->with('plain-password', 'hashed-password')->andReturn(false);

    $badge = new PasswordBadge('plain-password');
    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed-password'), [$badge]);

    $listener = new PasswordBadgeCheckListener($hasher);

    expect(fn () => $listener->checkPassport(createPasswordBadgeCheckListenerTestEvent($passport)))
        ->toThrow(InvalidPasswordException::class, 'Invalid password');
});

it('resolves password badge when verification succeeds', function (): void {
    /** @var MockInterface&PasswordHasherInterface $hasher */
    $hasher = Mockery::mock(PasswordHasherInterface::class);
    $hasher->shouldReceive('verify')->once()->with('plain-password', 'hashed-password')->andReturn(true);

    $badge = new PasswordBadge('plain-password');
    $passport = new Passport('alice', fn () => new MemoryUser('alice', 'hashed-password'), [$badge]);

    $listener = new PasswordBadgeCheckListener($hasher);
    $listener->checkPassport(createPasswordBadgeCheckListenerTestEvent($passport));

    expect($badge->isResolved())->toBeTrue();
});

function createPasswordBadgeCheckListenerTestEvent(Passport $passport): CheckPassportEvent
{
    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    return new CheckPassportEvent('main', $authenticator, $passport, $request);
}
