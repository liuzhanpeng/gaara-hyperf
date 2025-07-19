<?php

declare(strict_types=1);

use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\User\UserInterface;

describe('AuthenticatedToken', function () {
    $mockUser = function (string $id = 'u1'): UserInterface {
        $user = mock(UserInterface::class);
        $user->shouldReceive('getIdentifier')->andReturn($id);
        return $user;
    };

    it('can be instantiated and returns correct guard/user', function () use ($mockUser) {
        $user = $mockUser('test-user');
        $token = new AuthenticatedToken('my-guard', $user, ['foo' => 'bar']);
        expect($token->getGuardName())->toBe('my-guard');
        expect($token->getUser())->toBe($user);
        expect($token->hasAttribute('foo'))->toBeTrue();
        expect($token->getAttribute('foo'))->toBe('bar');
    });

    it('set and get attribute', function () use ($mockUser) {
        $user = $mockUser();
        $token = new AuthenticatedToken('g', $user);
        $token->setAttribute('a', 123);
        expect($token->getAttribute('a'))->toBe(123);
    });

    it('throws when getting missing attribute', function () use ($mockUser) {
        $user = $mockUser();
        $token = new AuthenticatedToken('g', $user);
        expect(fn() => $token->getAttribute('notfound'))->toThrow(InvalidArgumentException::class);
    });

    it('serializes and unserializes', function () use ($mockUser) {
        $user = $mockUser('u2');
        $token = new AuthenticatedToken('g', $user, ['x' => 1]);
        $arr = $token->__serialize();
        $token2 = new AuthenticatedToken('dummy', $user);
        $token2->__unserialize($arr);
        expect($token2->getGuardName())->toBe('g');
        expect($token2->getUser()->getIdentifier())->toBe('u2');
        expect($token2->getAttribute('x'))->toBe(1);
    });

    it('toString returns expected format', function () use ($mockUser) {
        $user = $mockUser('id99');
        $token = new AuthenticatedToken('g', $user, ['a' => 1]);
        expect((string)$token)->toContain('AuthenticatedToken');
        expect((string)$token)->toContain('id99');
        expect((string)$token)->toContain('a');
    });
});
