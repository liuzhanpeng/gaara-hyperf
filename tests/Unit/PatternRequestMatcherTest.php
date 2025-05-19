<?php

declare(strict_types=1);

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\RquestMatcher\PatternRequestMatcher;

test('PatternRequestMatcherTest', function () {
    $request = Mockery::mock(RequestInterface::class);
    $request->shouldReceive('getPathInfo')->andReturn(
        '/admin/',
        '/admin/users',
        '/admin/users/1',
        '/admin/users/1/edit',
        '/admin2/',
        '/admin2/users',
        '/admin/logout'
    );

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
