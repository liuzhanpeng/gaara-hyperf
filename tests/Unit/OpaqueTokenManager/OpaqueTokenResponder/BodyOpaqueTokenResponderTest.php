<?php

declare(strict_types=1);

use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\BodyOpaqueTokenResponder;
use GaaraHyperf\Token\TokenInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HyperfResponseInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;

afterEach(function (): void {
    Mockery::close();
});

it('returns json response with default template', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $jsonResponse */
    $jsonResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $token->shouldReceive('getUserIdentifier')->once()->andReturn('user-1');
    $response->shouldReceive('json')->once()->with([
        'code' => 0,
        'message' => 'success',
        'data' => [
            'access_token' => 'opaque-token',
            'expires_in' => 3600,
            'user_identifier' => 'user-1',
        ],
    ])->andReturn($jsonResponse);

    $opaqueToken = new OpaqueToken($token, 'opaque-token', time(), 3600);
    $responder = new BodyOpaqueTokenResponder($response);

    expect($responder->respond($opaqueToken))->toBe($jsonResponse);
});

it('returns json response with custom template', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $jsonResponse */
    $jsonResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $token->shouldReceive('getUserIdentifier')->once()->andReturn('user-2');
    $response->shouldReceive('json')->once()->with([
        'token' => 'opaque-token-2',
        'exp' => 1800,
        'user' => 'user-2',
    ])->andReturn($jsonResponse);

    $opaqueToken = new OpaqueToken($token, 'opaque-token-2', time(), 1800);
    $responder = new BodyOpaqueTokenResponder(
        response: $response,
        template: '{"token":"#ACCESS_TOKEN#","exp":#EXPIRES_IN#,"user":"#USER_IDENTIFIER#"}'
    );

    expect($responder->respond($opaqueToken))->toBe($jsonResponse);
});

it('throws when response template is invalid json', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $token->shouldReceive('getUserIdentifier')->once()->andReturn('user-3');
    $response->shouldNotReceive('json');

    $opaqueToken = new OpaqueToken($token, 'opaque-token-3', time(), 1800);
    $responder = new BodyOpaqueTokenResponder($response, '{invalid-json');

    expect(fn () => $responder->respond($opaqueToken))
        ->toThrow(InvalidArgumentException::class, 'Response template must be a valid JSON string');
});
