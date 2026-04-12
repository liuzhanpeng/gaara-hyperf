<?php

declare(strict_types=1);

use GaaraHyperf\Authentication\DefaultAuthenticationTrustDecider;
use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\Token\TokenInterface;

describe('DefaultAuthenticationTrustDecider', function () {
    it('returns true for authenticated token', function () {
        $decider = new DefaultAuthenticationTrustDecider();

        expect($decider->isAuthenticated(new AuthenticatedToken('admin', 'u1')))->toBeTrue();
    });

    it('returns false for null token', function () {
        $decider = new DefaultAuthenticationTrustDecider();

        expect($decider->isAuthenticated(null))->toBeFalse();
    });

    it('returns false for non-authenticated token implementation', function () {
        $decider = new DefaultAuthenticationTrustDecider();

        $token = new class implements TokenInterface {
            public function getGuardName(): string
            {
                return 'admin';
            }

            public function getUserIdentifier(): string
            {
                return 'u1';
            }

            public function hasAttribute(string $name): bool
            {
                return false;
            }

            public function getAttribute(string $name): mixed
            {
                return null;
            }

            public function setAttribute(string $name, mixed $value): void
            {
            }
        };

        expect($decider->isAuthenticated($token))->toBeFalse();
    });
});
