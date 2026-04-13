<?php

declare(strict_types=1);

use GaaraHyperf\AccessTokenExtractor\BodyAccessTokenExtractor;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

describe('BodyAccessTokenExtractor', function () {
    it('extracts valid token from parsed body', function (): void {
        $extractor = new BodyAccessTokenExtractor('access_token');
        $request = createBodyAccessTokenExtractorTestRequest(['access_token' => 'abcDEF123-_+/~.=']);

        expect($extractor->extract($request))->toBe('abcDEF123-_+/~.=');
    });

    it('returns null when parsed body is not array', function (): void {
        $extractor = new BodyAccessTokenExtractor('access_token');
        $request = createBodyAccessTokenExtractorTestRequest('not-array');

        expect($extractor->extract($request))->toBeNull();
    });

    it('returns null when token field is missing', function (): void {
        $extractor = new BodyAccessTokenExtractor('access_token');
        $request = createBodyAccessTokenExtractorTestRequest(['other' => 'value']);

        expect($extractor->extract($request))->toBeNull();
    });

    it('returns null when token is empty or not string', function (): void {
        $extractor = new BodyAccessTokenExtractor('access_token');

        $emptyRequest = createBodyAccessTokenExtractorTestRequest(['access_token' => '']);
        $arrayRequest = createBodyAccessTokenExtractorTestRequest(['access_token' => ['nested']]);

        expect($extractor->extract($emptyRequest))->toBeNull();
        expect($extractor->extract($arrayRequest))->toBeNull();
    });

    it('returns null for invalid token format', function (): void {
        $extractor = new BodyAccessTokenExtractor('access_token');
        $request = createBodyAccessTokenExtractorTestRequest(['access_token' => 'bad token !!']);

        expect($extractor->extract($request))->toBeNull();
    });
});

function createBodyAccessTokenExtractorTestRequest(mixed $parsedBody): ServerRequestInterface
{
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getParsedBody')->andReturn($parsedBody);

    return $request;
}
