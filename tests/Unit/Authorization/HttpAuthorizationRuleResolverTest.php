<?php

declare(strict_types=1);

use GaaraHyperf\Authorization\HttpAuthorizationRuleResolver;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

afterEach(function (): void {
    Mockery::close();
});

it('resolves object as request path and action as uppercased method', function (): void {
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);

    $request->shouldReceive('getUri')->once()->andReturn($uri);
    $uri->shouldReceive('getPath')->once()->andReturn('/admin/orders/1');
    $request->shouldReceive('getMethod')->once()->andReturn('post');

    $resolver = new HttpAuthorizationRuleResolver();

    expect($resolver->resolve($request))->toBe([
        'object' => '/admin/orders/1',
        'action' => 'POST',
    ]);
});
