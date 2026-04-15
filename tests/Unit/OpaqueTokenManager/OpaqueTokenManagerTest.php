<?php

declare(strict_types=1);

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManager;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderInterface;
use GaaraHyperf\Token\TokenInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('issues token and returns responder response', function (): void {
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ResponseInterface $response */
    $response = Mockery::mock(ResponseInterface::class);

    $opaqueToken = new OpaqueToken($token, 'opaque-token', time(), 3600);

    $issuer->shouldReceive('issue')->once()->with($token)->andReturn($opaqueToken);
    $responder->shouldReceive('respond')->once()->with($opaqueToken)->andReturn($response);

    $manager = new OpaqueTokenManager($issuer, $extractor, $responder);

    expect($manager->issue($token))->toBe($response);
});

it('returns null when no access token can be extracted', function (): void {
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $extractor->shouldReceive('extract')->once()->with($request)->andReturnNull();
    $issuer->shouldNotReceive('resolve');

    $manager = new OpaqueTokenManager($issuer, $extractor, $responder);

    expect($manager->resolve($request))->toBeNull();
});

it('resolves opaque token from extracted access token', function (): void {
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    $opaqueToken = new OpaqueToken($token, 'resolved-token', time(), 3600);

    $extractor->shouldReceive('extract')->once()->with($request)->andReturn('resolved-token');
    $issuer->shouldReceive('resolve')->once()->with('resolved-token')->andReturn($opaqueToken);

    $manager = new OpaqueTokenManager($issuer, $extractor, $responder);

    expect($manager->resolve($request))->toBe($opaqueToken);
});

it('does nothing on revoke when no access token can be extracted', function (): void {
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $extractor->shouldReceive('extract')->once()->with($request)->andReturnNull();
    $issuer->shouldNotReceive('revoke');

    $manager = new OpaqueTokenManager($issuer, $extractor, $responder);
    $manager->revoke($request);

    expect(true)->toBeTrue();
});

it('revokes extracted access token via issuer', function (): void {
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $extractor->shouldReceive('extract')->once()->with($request)->andReturn('to-revoke');
    $issuer->shouldReceive('revoke')->once()->with('to-revoke');

    $manager = new OpaqueTokenManager($issuer, $extractor, $responder);
    $manager->revoke($request);

    expect(true)->toBeTrue();
});
