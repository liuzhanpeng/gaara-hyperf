<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\OpaqueTokenSuccessHandler;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('resolves default token manager and returns issued response', function (): void {
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $resolver->shouldReceive('resolve')->once()->with('default')->andReturn($manager);
    $manager->shouldReceive('issue')->once()->with($token)->andReturn($response);

    $result = (new OpaqueTokenSuccessHandler($resolver))
        ->handle('api', Mockery::mock(ServerRequestInterface::class), $token, Mockery::mock(Passport::class));

    expect($result)->toBe($response);
});

it('resolves custom token manager when specified', function (): void {
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $resolver->shouldReceive('resolve')->once()->with('redis')->andReturn($manager);
    $manager->shouldReceive('issue')->once()->with($token)->andReturn($response);

    $result = (new OpaqueTokenSuccessHandler($resolver, 'redis'))
        ->handle('api', Mockery::mock(ServerRequestInterface::class), $token, Mockery::mock(Passport::class));

    expect($result)->toBe($response);
});
