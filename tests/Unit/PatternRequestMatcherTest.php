<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\RquestMatcher\PatternRequestMatcher;
use Psr\Http\Message\UriInterface;

test('PatternRequestMatcherTest', function () {
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('getPath')
        ->andReturn(
            '/admin/',
            '/admin/users',
            '/admin/users/1',
            '/admin/users/1/edit',
            '/admin2/',
            '/admin2/users',
            '/admin/logout'
        );
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getUri')
        ->andReturn($uri);

    $requestMatcher = new PatternRequestMatcher('^/admin/', [
        '/admin/logout',
    ]);
    expect($requestMatcher->matches($request))->toBeTrue();
    expect($requestMatcher->matches($request))->toBeTrue();
    expect($requestMatcher->matches($request))->toBeTrue();
    expect($requestMatcher->matches($request))->toBeTrue();
    expect($requestMatcher->matches($request))->toBeFalse();
    expect($requestMatcher->matches($request))->toBeFalse();
    expect($requestMatcher->matches($request))->toBeFalse();
});
