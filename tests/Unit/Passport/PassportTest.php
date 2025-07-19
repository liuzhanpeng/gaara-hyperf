<?php

declare(strict_types=1);

use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\User\UserInterface;
use Lzpeng\HyperfAuthGuard\Passport\BadgeInterface;
use Lzpeng\HyperfAuthGuard\Exception\UserNotFoundException;

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

    it('returns guard name', function () {
        $passport = new Passport('guardA', 'id1', fn($id) => null, []);
        expect($passport->getGuardName())->toBe('guardA');
    });

    it('loads user via loader', function () {
        $user = mockUser('id2');
        $passport = new Passport('g', 'id2', fn($id) => $user, []);
        expect($passport->getUser())->toBe($user);
    });

    it('throws if user not found', function () {
        $passport = new Passport('g', 'id3', fn($id) => null, []);
        expect(fn() => $passport->getUser())->toThrow(UserNotFoundException::class);
    });

    it('throws if userLoader returns non-UserInterface', function () {
        $passport = new Passport('g', 'id4', fn($id) => 123, []);
        expect(fn() => $passport->getUser())->toThrow(LogicException::class);
    });

    it('can add and get badge', function () {
        $user = mockUser();
        $badge = mockBadge();
        $passport = new Passport('g', 'u1', fn($id) => $user, []);
        $passport->addBadge($badge);
        expect($passport->getBadge(get_class($badge)))->toBe($badge);
    });

    it('returns all badges', function () {
        $user = mockUser();
        $badgeA = mockBadge();
        $badgeB = mockBadge();
        $passport = new Passport('g', 'u1', fn($id) => $user, [$badgeA, $badgeB]);
        $badges = $passport->getBadges();
        expect($badges)->toHaveKey(get_class($badgeA));
        expect($badges)->toHaveKey(get_class($badgeB));
    });
});
