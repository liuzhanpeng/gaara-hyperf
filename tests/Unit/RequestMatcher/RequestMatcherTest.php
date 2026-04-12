<?php

declare(strict_types=1);

use GaaraHyperf\RequestMatcher\RequestMatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

describe('RequestMatcher', function () {
    function mockRequest(string $path, string $method = 'GET'): ServerRequestInterface
    {
        $uri = mock(UriInterface::class);
        $uri->shouldReceive('getPath')->andReturn($path);
        $request = mock(ServerRequestInterface::class);
        $request->shouldReceive('getUri')->andReturn($uri);
        $request->shouldReceive('getMethod')->andReturn($method);
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
        $matcher = new RequestMatcher('/api', '^/logout$', []);
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

    it('matches pattern with method prefix', function () {
        $matcher = new RequestMatcher('GET /api/users', null, []);
        expect($matcher->matchesPattern(mockRequest('/api/users', 'GET')))->toBeTrue();
        expect($matcher->matchesPattern(mockRequest('/api/users', 'POST')))->toBeFalse();
        expect($matcher->matchesPattern(mockRequest('/api/other', 'GET')))->toBeFalse();
    });

    it('matches pattern with multiple methods', function () {
        $matcher = new RequestMatcher('POST|PUT /api/users/\d+', null, []);
        expect($matcher->matchesPattern(mockRequest('/api/users/1', 'POST')))->toBeTrue();
        expect($matcher->matchesPattern(mockRequest('/api/users/1', 'PUT')))->toBeTrue();
        expect($matcher->matchesPattern(mockRequest('/api/users/1', 'GET')))->toBeFalse();
        expect($matcher->matchesPattern(mockRequest('/api/users/abc', 'POST')))->toBeFalse();
    });

    it('matches logout with method prefix', function () {
        $matcher = new RequestMatcher('/api', 'POST /logout', []);
        expect($matcher->matchesLogout(mockRequest('/logout', 'POST')))->toBeTrue();
        expect($matcher->matchesLogout(mockRequest('/logout', 'GET')))->toBeFalse();
    });

    it('matches excluded with method prefix', function () {
        $matcher = new RequestMatcher('/api', null, ['GET|HEAD /api/public']);
        expect($matcher->matchesExcluded(mockRequest('/api/public', 'GET')))->toBeTrue();
        expect($matcher->matchesExcluded(mockRequest('/api/public', 'HEAD')))->toBeTrue();
        expect($matcher->matchesExcluded(mockRequest('/api/public', 'POST')))->toBeFalse();
    });

    it('matches plain string as prefix without regex', function () {
        $matcher = new RequestMatcher('/api', null, []);
        expect($matcher->matchesPattern(mockRequest('/api/users', 'GET')))->toBeTrue();
        expect($matcher->matchesPattern(mockRequest('/api', 'GET')))->toBeTrue();
        expect($matcher->matchesPattern(mockRequest('/other', 'GET')))->toBeFalse();
    });

    it('treats string and array inputs consistently', function () {
        $stringMatcher = new RequestMatcher('get /api/users', null, []);
        $arrayMatcher = new RequestMatcher(['get /api/users'], null, []);

        expect($stringMatcher->matchesPattern(mockRequest('/api/users', 'GET')))->toBeTrue();
        expect($arrayMatcher->matchesPattern(mockRequest('/api/users', 'GET')))->toBeTrue();
        expect($stringMatcher->matchesPattern(mockRequest('/api/users', 'POST')))->toBeFalse();
        expect($arrayMatcher->matchesPattern(mockRequest('/api/users', 'POST')))->toBeFalse();
    });

    it('returns false for logout and exclusions when not configured', function () {
        $matcher = new RequestMatcher('/api', null, []);

        expect($matcher->matchesLogout(mockRequest('/api/logout', 'POST')))->toBeFalse();
        expect($matcher->matchesExcluded(mockRequest('/api/public', 'GET')))->toBeFalse();
    });
});
