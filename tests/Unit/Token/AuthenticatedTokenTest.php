<?php

declare(strict_types=1);

use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\User\UserInterface;

describe('AuthenticatedToken', function () {
    it('can be instantiated and returns correct guard/user', function () {
        $user = 'u1';
        $token = new AuthenticatedToken('my-guard', $user, ['foo' => 'bar']);
        expect($token->getGuardName())->toBe('my-guard');
        expect($token->getUserIdentifier())->toBe($user);
        expect($token->hasAttribute('foo'))->toBeTrue();
        expect($token->getAttribute('foo'))->toBe('bar');
    });

    it('set and get attribute', function () {
        $user = 'u1';
        $token = new AuthenticatedToken('g', $user);
        $token->setAttribute('a', 123);
        expect($token->getAttribute('a'))->toBe(123);
    });

    it('throws when getting missing attribute', function () {
        $user = 'u1';
        $token = new AuthenticatedToken('g', $user);
        expect(fn() => $token->getAttribute('notfound'))->toThrow(InvalidArgumentException::class);
    });

    it('serializes and unserializes', function () {
        $user = 'u2';
        $token = new AuthenticatedToken('g', $user, ['x' => 1]);
        $arr = $token->__serialize();
        $token2 = new AuthenticatedToken('dummy', $user);
        $token2->__unserialize($arr);
        expect($token2->getGuardName())->toBe('g');
        expect($token2->getUserIdentifier())->toBe('u2');
        expect($token2->getAttribute('x'))->toBe(1);
    });

    it('toString returns expected format', function () {
        $user = 'id99';
        $token = new AuthenticatedToken('g', $user, ['a' => 1]);
        expect((string)$token)->toContain('AuthenticatedToken');
        expect((string)$token)->toContain('id99');
        expect((string)$token)->toContain('a');
    });
});
