<?php

declare(strict_types=1);

use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\Passport\BadgeInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\User\UserInterface;

describe('Passport', function () {
    function mockUser(string $id = 'u1'): UserInterface
    {
        $user = mock(UserInterface::class);
        $user->shouldReceive('getIdentifier')->andReturn($id);
        return $user;
    }
    function mockBadge(): BadgeInterface
    {
        return new class implements BadgeInterface {
            public function isResolved(): bool
            {
                return true;
            }
        };
    }

    it('loads user via loader', function () {
        $user = mockUser('id2');
        $passport = new Passport('id2', fn ($id) => $user, []);
        expect($passport->getUser())->toBe($user);
    });

    it('does not load user during construction', function () {
        $calls = 0;

        $passport = new Passport('id3', function ($id) use (&$calls) {
            ++$calls;
            return mockUser($id);
        }, []);

        expect($calls)->toBe(0)
            ->and($passport->getUser()->getIdentifier())->toBe('id3')
            ->and($calls)->toBe(1);
    });

    it('throws if user not found', function () {
        $passport = new Passport('id3', fn ($id) => null, []);

        expect(fn () => $passport->getUser())
            ->toThrow(UserNotFoundException::class);
    });

    it('throws if userLoader returns non-UserInterface', function () {
        $passport = new Passport('id4', fn ($id) => 123, []);

        expect(fn () => $passport->getUser())
            ->toThrow(RuntimeException::class);
    });

    it('can add and get badge', function () {
        $user = mockUser();
        $badge = mockBadge();
        $passport = new Passport('u1', fn ($id) => $user, []);
        $passport->addBadge($badge);
        expect($passport->getBadge(get_class($badge)))->toBe($badge);
    });

    it('returns all badges', function () {
        $user = mockUser();
        $badgeA = mockBadge();
        $badgeB = mockBadge();
        $passport = new Passport('u1', fn ($id) => $user, [$badgeA, $badgeB]);
        $badges = $passport->getBadges();
        expect($badges)->toHaveKey(get_class($badgeA));
        expect($badges)->toHaveKey(get_class($badgeB));
    });
});
