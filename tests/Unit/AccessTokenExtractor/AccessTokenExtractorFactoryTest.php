<?php

declare(strict_types=1);

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorFactory;
use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\AccessTokenExtractor\BodyAccessTokenExtractor;
use GaaraHyperf\AccessTokenExtractor\CookieAccessTokenExtractor;
use GaaraHyperf\AccessTokenExtractor\HeaderAccessTokenExtractor;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('creates header extractor with defaults', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);

    $container->shouldReceive('make')->once()->with(HeaderAccessTokenExtractor::class, [
        'field' => 'Authorization',
        'scheme' => 'Bearer',
    ])->andReturn($extractor);

    $factory = new AccessTokenExtractorFactory($container);

    expect($factory->create([]))->toBe($extractor);
});

it('creates cookie extractor with custom field', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);

    $container->shouldReceive('make')->once()->with(CookieAccessTokenExtractor::class, [
        'field' => 'my_cookie',
    ])->andReturn($extractor);

    $factory = new AccessTokenExtractorFactory($container);

    expect($factory->create([
        'type' => 'cookie',
        'field' => 'my_cookie',
    ]))->toBe($extractor);
});

it('creates body extractor with custom field', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);

    $container->shouldReceive('make')->once()->with(BodyAccessTokenExtractor::class, [
        'field' => 'token_body',
    ])->andReturn($extractor);

    $factory = new AccessTokenExtractorFactory($container);

    expect($factory->create([
        'type' => 'body',
        'field' => 'token_body',
    ]))->toBe($extractor);
});

it('creates custom extractor and maps params to camel case', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomExtractorForAccessTokenExtractorFactoryTest::class,
        ['tokenField' => 'x-token']
    )->andReturn($extractor);

    $factory = new AccessTokenExtractorFactory($container);

    expect($factory->create([
        'type' => 'custom',
        'class' => CustomExtractorForAccessTokenExtractorFactoryTest::class,
        'params' => ['token_field' => 'x-token'],
    ]))->toBe($extractor);
});

it('throws when custom extractor does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotExtractorForAccessTokenExtractorFactoryTest::class, [])->andReturn(new NotExtractorForAccessTokenExtractorFactoryTest());

    $factory = new AccessTokenExtractorFactory($container);

    expect(fn () => $factory->create([
        'type' => 'custom',
        'class' => NotExtractorForAccessTokenExtractorFactoryTest::class,
    ]))->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when type is unsupported', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new AccessTokenExtractorFactory($container);

    expect(fn () => $factory->create(['type' => 'unknown']))
        ->toThrow(InvalidArgumentException::class, 'Access Token Extractor type does not exist');
});

class CustomExtractorForAccessTokenExtractorFactoryTest implements AccessTokenExtractorInterface
{
    public function extract(ServerRequestInterface $request): ?string
    {
        return null;
    }
}

class NotExtractorForAccessTokenExtractorFactoryTest
{
}
