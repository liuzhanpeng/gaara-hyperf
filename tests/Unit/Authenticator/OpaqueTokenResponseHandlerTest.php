<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\OpaqueTokenResponseHandler;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HyperfResponseInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('returns json response with default template', function (): void {
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $jsonResponse */
    $jsonResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&Passport $passport */
    $passport = Mockery::mock(Passport::class);

    $resolver->shouldReceive('resolve')->once()->with('default')->andReturn($manager);
    $manager->shouldReceive('issue')->once()->with($token)->andReturn('opaque-token-1');
    $token->shouldReceive('getUserIdentifier')->once()->andReturn('user-1');

    $response->shouldReceive('json')->once()->with([
        'user_identifier' => 'user-1',
        'access_token' => 'opaque-token-1',
    ])->andReturn($jsonResponse);

    $handler = new OpaqueTokenResponseHandler($resolver, $response);

    expect($handler->handle('api', $request, $token, $passport))->toBe($jsonResponse);
});

it('returns json response with custom template placeholders', function (): void {
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $jsonResponse */
    $jsonResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&Passport $passport */
    $passport = Mockery::mock(Passport::class);

    $resolver->shouldReceive('resolve')->once()->with('custom')->andReturn($manager);
    $manager->shouldReceive('issue')->once()->with($token)->andReturn('opaque-token-2');
    $token->shouldReceive('getUserIdentifier')->once()->andReturn('u2');

    $response->shouldReceive('json')->once()->with([
        'token' => 'opaque-token-2',
        'user' => 'u2',
        'meta' => ['guard' => 'api'],
    ])->andReturn($jsonResponse);

    $handler = new OpaqueTokenResponseHandler(
        opaqueTokenManagerResolver: $resolver,
        response: $response,
        tokenManager: 'custom',
        responseTemplate: '{"token":"#ACCESS_TOKEN#","user":"#USER_IDENTIFIER#","meta":{"guard":"api"}}',
    );

    expect($handler->handle('api', $request, $token, $passport))->toBe($jsonResponse);
});

it('throws exception when response template is invalid json', function (): void {
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&Passport $passport */
    $passport = Mockery::mock(Passport::class);

    $resolver->shouldReceive('resolve')->once()->with('default')->andReturn($manager);
    $manager->shouldReceive('issue')->once()->with($token)->andReturn('opaque-token-3');
    $token->shouldReceive('getUserIdentifier')->once()->andReturn('u3');

    $handler = new OpaqueTokenResponseHandler(
        opaqueTokenManagerResolver: $resolver,
        response: $response,
        tokenManager: 'default',
        responseTemplate: '{"access_token": #ACCESS_TOKEN#}',
    );

    expect(fn () => $handler->handle('api', $request, $token, $passport))
        ->toThrow(InvalidArgumentException::class, 'Response template must be a valid JSON string');
});
