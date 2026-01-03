<?php

declare(strict_types=1);

describe('HeaderAccessTokenExtractor', function () {

    beforeEach(function () {
        $this->extractor = new Lzpeng\HyperfAuthGuard\AccessTokenExtractor\HeaderAccessTokenExtractor('Authorization');
        $this->request = mock(Psr\Http\Message\ServerRequestInterface::class);
    });

    it('should extract access token from Authorization header', function () {

        $this->request->shouldReceive('getHeaderLine')
            ->with('Authorization')
            ->andReturn('Bearer valid_token_123');
        $this->request->shouldReceive('hasHeader')
            ->with('Authorization')
            ->andReturn(true);

        $token = $this->extractor->extractAccessToken($this->request);
        expect($token)->toBe('valid_token_123');
    });

    it('should return null if Authorization header is missing', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('Authorization')
            ->andReturn('');
        $this->request->shouldReceive('hasHeader')
            ->with('Authorization')
            ->andReturn(false);

        $token = $this->extractor->extractAccessToken($this->request);
        expect($token)->toBeNull();
    });

    it('should return null for invalid token format', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('Authorization')
            ->andReturn('InvalidFormatToken');

        $this->request->shouldReceive('hasHeader')
            ->with('Authorization')
            ->andReturn(true);

        $token = $this->extractor->extractAccessToken($this->request);
        expect($token)->toBeNull();
    });
});
