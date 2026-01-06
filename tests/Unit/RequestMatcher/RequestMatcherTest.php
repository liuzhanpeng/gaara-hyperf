<?php

declare(strict_types=1);

use GaaraHyperf\RequestMatcher\RequestMatcher;
use Psr\Http\Message\ServerRequestInterface;

describe('RequestMatcher', function () {
    function mockRequest(string $path): ServerRequestInterface
    {
        $uri = mock(\Psr\Http\Message\UriInterface::class);
        $uri->shouldReceive('getPath')->andReturn($path);
        $request = mock(ServerRequestInterface::class);
        $request->shouldReceive('getUri')->andReturn($uri);
        return $request;
    }

    it('matches pattern by regex', function () {
        $matcher = new RequestMatcher('^/foo/[0-9]+$', '/logout', []);
        $request = mockRequest('/foo/123');
        expect($matcher->matchesPattern($request))->toBeTrue();
        $request2 = mockRequest('/foo/abc');
        expect($matcher->matchesPattern($request2))->toBeFalse();
    });

    it('matches logout', function () {
        $matcher = new RequestMatcher('/api', '/logout', []);
        $request = mockRequest('/logout');
        expect($matcher->matchesLogout($request))->toBeTrue();
        $request2 = mockRequest('/api/logout');
        expect($matcher->matchesLogout($request2))->toBeFalse();
    });

    it('matches excluded', function () {
        $matcher = new RequestMatcher('/api', '/logout', ['^/api/ex[0-9]+$', '^/foo.*$']);
        $request = mockRequest('/api/ex123');
        expect($matcher->matchesExcluded($request))->toBeTrue();
        $request2 = mockRequest('/foobar');
        expect($matcher->matchesExcluded($request2))->toBeTrue();
        $request3 = mockRequest('/api/user');
        expect($matcher->matchesExcluded($request3))->toBeFalse();
    });

    it('matches pattern with exact match', function () {
        $matcher = new RequestMatcher('^/api/user$', '/logout', []);
        $request = mockRequest('/api/user');
        expect($matcher->matchesPattern($request))->toBeTrue();
        $request2 = mockRequest('/api/user/1');
        expect($matcher->matchesPattern($request2))->toBeFalse();
    });

    it('matches pattern with wildcard', function () {
        $matcher = new RequestMatcher('^/api/user/.*$', '/logout', []);
        $request = mockRequest('/api/user');
        expect($matcher->matchesPattern($request))->toBeFalse();
        $request2 = mockRequest('/api/user/1');
        expect($matcher->matchesPattern($request2))->toBeTrue();
    });
});
