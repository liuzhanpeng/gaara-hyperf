<?php

declare(strict_types=1);

describe('SessionCsrfTokenManager', function () {
    beforeEach(function () {
        $this->session = mock(Hyperf\Contract\SessionInterface::class);
        $this->csrfTokenManager = new GaaraHyperf\CsrfTokenManager\SessionCsrfTokenManager('csrf', $this->session);
    });

    it('should generate a CSRF token and store it in session', function () {
        $this->session->shouldReceive('set')
            ->once()
            ->withArgs(function ($key, $value) {
                expect($key)->toBe('csrf.authenticate');
                expect(strlen($value))->toBe(32);
                return true;
            });

        $token = $this->csrfTokenManager->generate('authenticate');
        expect($token)->toBeInstanceOf(GaaraHyperf\CsrfTokenManager\CsrfToken::class);
        expect($token->getId())->toBe('authenticate');
        expect(strlen($token->getValue()))->toBe(32);
    });

    it('should verify a valid CSRF token', function () {
        $this->session->shouldReceive('get')
            ->once()
            ->with('csrf.authenticate')
            ->andReturn('valid_token_value');

        $token = new GaaraHyperf\CsrfTokenManager\CsrfToken('authenticate', 'valid_token_value');
        $isValid = $this->csrfTokenManager->verify($token);
        expect($isValid)->toBeTrue();
    });

    it('should not verify an invalid CSRF token', function () {
        $this->session->shouldReceive('get')
            ->once()
            ->with('csrf.authenticate')
            ->andReturn('valid_token_value');

        $token = new GaaraHyperf\CsrfTokenManager\CsrfToken('authenticate', 'invalid_token_value');
        $isValid = $this->csrfTokenManager->verify($token);
        expect($isValid)->toBeFalse();
    });
});
