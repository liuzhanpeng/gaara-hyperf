<?php

declare(strict_types=1);

use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\CookieOpaqueTokenResponder;
use GaaraHyperf\Token\TokenInterface;
use Hyperf\HttpMessage\Cookie\Cookie;
use Hyperf\HttpServer\Contract\ResponseInterface as HyperfResponseInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;

afterEach(function (): void {
    Mockery::close();
});

it('writes opaque token to cookie and returns json response', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $jsonResponse */
    $jsonResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $token->shouldReceive('getUserIdentifier')->once()->andReturn('user-1');

    $response->shouldReceive('withCookie')->once()->with(Mockery::on(function (mixed $cookie): bool {
        return $cookie instanceof Cookie
            && $cookie->getName() === 'access_token'
            && $cookie->getValue() === 'cookie-token'
            && $cookie->getPath() === '/'
            && $cookie->isSecure() === true
            && $cookie->isHttpOnly() === true
            && $cookie->getSameSite() === 'lax';
    }))->andReturnSelf();

    $response->shouldReceive('json')->once()->with([
        'code' => 0,
        'message' => 'success',
    ])->andReturn($jsonResponse);

    $opaqueToken = new OpaqueToken($token, 'cookie-token', time(), 3600);
    $responder = new CookieOpaqueTokenResponder($response);

    expect($responder->respond($opaqueToken))->toBe($jsonResponse);
});

it('uses custom cookie options and template', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&ResponseInterface $jsonResponse */
    $jsonResponse = Mockery::mock(ResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $token->shouldReceive('getUserIdentifier')->once()->andReturn('user-2');

    $response->shouldReceive('withCookie')->once()->with(Mockery::on(function (mixed $cookie): bool {
        return $cookie instanceof Cookie
            && $cookie->getName() === 'my_token'
            && $cookie->getValue() === 'cookie-token-2'
            && $cookie->getPath() === '/api'
            && $cookie->getDomain() === 'example.com'
            && $cookie->isSecure() === false
            && $cookie->isHttpOnly() === false
            && $cookie->getSameSite() === 'strict';
    }))->andReturnSelf();

    $response->shouldReceive('json')->once()->with([
        'token' => 'cookie-token-2',
        'user' => 'user-2',
    ])->andReturn($jsonResponse);

    $opaqueToken = new OpaqueToken($token, 'cookie-token-2', time(), 1800);
    $responder = new CookieOpaqueTokenResponder(
        response: $response,
        cookieName: 'my_token',
        cookiePath: '/api',
        cookieDomain: 'example.com',
        cookieSecure: false,
        cookieHttpOnly: false,
        cookieSameSite: 'strict',
        template: '{"token":"#ACCESS_TOKEN#","user":"#USER_IDENTIFIER#"}',
    );

    expect($responder->respond($opaqueToken))->toBe($jsonResponse);
});

it('throws when cookie responder template is invalid json', function (): void {
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $token->shouldReceive('getUserIdentifier')->once()->andReturn('user-3');
    $response->shouldReceive('withCookie')->once()->andReturnSelf();
    $response->shouldNotReceive('json');

    $opaqueToken = new OpaqueToken($token, 'cookie-token-3', time(), 1800);
    $responder = new CookieOpaqueTokenResponder($response, template: '{bad json');

    expect(fn () => $responder->respond($opaqueToken))
        ->toThrow(InvalidArgumentException::class, 'Response template must be a valid JSON string');
});
