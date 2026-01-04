<?php

declare(strict_types=1);

use GaaraHyperf\AccessTokenExtractor\CookieAccessTokenExtractor;
use Psr\Http\Message\ServerRequestInterface;

describe('CookieAccessTokenExtractor', function () {

    beforeEach(function () {
        $this->extractor = new CookieAccessTokenExtractor('access_token');
        $this->request = mock(ServerRequestInterface::class);
    });

    it('should extract access token from cookies', function () {

        $this->request->shouldReceive('getCookieParams')
            ->andReturn([
                'access_token' => 'valid_token_123',
            ]);

        $token = $this->extractor->extractAccessToken($this->request);
        expect($token)->toBe('valid_token_123');
    });

    it('should return null if access token cookie is missing', function () {
        $this->request->shouldReceive('getCookieParams')
            ->andReturn([]);

        $token = $this->extractor->extractAccessToken($this->request);
        expect($token)->toBeNull();
    });

    it('should return null for invalid token format', function () {
        $this->request->shouldReceive('getCookieParams')
            ->andReturn([
                'access_token' => 'invalid token!',
            ]);

        $token = $this->extractor->extractAccessToken($this->request);
        expect($token)->toBeNull();
    });
});
