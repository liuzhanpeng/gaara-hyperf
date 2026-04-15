<?php

declare(strict_types=1);

use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\BodyOpaqueTokenResponder;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\CookieOpaqueTokenResponder;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;

afterEach(function (): void {
    Mockery::close();
});

it('creates body responder with defaults', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);

    $container->shouldReceive('make')->once()->with(BodyOpaqueTokenResponder::class, [
        'template' => null,
    ])->andReturn($responder);

    $factory = new OpaqueTokenResponderFactory($container);

    expect($factory->create([]))->toBe($responder);
});

it('creates cookie responder with custom options', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);

    $container->shouldReceive('make')->once()->with(CookieOpaqueTokenResponder::class, [
        'cookieName' => 'my_token',
        'cookiePath' => '/api',
        'cookieDomain' => 'example.com',
        'cookieSecure' => false,
        'cookieHttpOnly' => false,
        'cookieSameSite' => 'strict',
        'template' => '{"ok":true}',
    ])->andReturn($responder);

    $factory = new OpaqueTokenResponderFactory($container);

    expect($factory->create([
        'type' => 'cookie',
        'cookie_name' => 'my_token',
        'cookie_path' => '/api',
        'cookie_domain' => 'example.com',
        'cookie_secure' => false,
        'cookie_http_only' => false,
        'cookie_same_site' => 'strict',
        'template' => '{"ok":true}',
    ]))->toBe($responder);
});

it('creates custom responder and maps params to camel case', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomResponderForOpaqueTokenResponderFactoryTest::class,
        ['cookieName' => 'x-token']
    )->andReturn($responder);

    $factory = new OpaqueTokenResponderFactory($container);

    expect($factory->create([
        'type' => 'custom',
        'class' => CustomResponderForOpaqueTokenResponderFactoryTest::class,
        'params' => ['cookie_name' => 'x-token'],
    ]))->toBe($responder);
});

it('throws when custom responder does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotResponderForOpaqueTokenResponderFactoryTest::class, [])->andReturn(new NotResponderForOpaqueTokenResponderFactoryTest());

    $factory = new OpaqueTokenResponderFactory($container);

    expect(fn () => $factory->create([
        'type' => 'custom',
        'class' => NotResponderForOpaqueTokenResponderFactoryTest::class,
    ]))->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when responder type is unsupported', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new OpaqueTokenResponderFactory($container);

    expect(fn () => $factory->create(['type' => 'unknown']))
        ->toThrow(InvalidArgumentException::class, 'Opaque Token Responder type does not exist');
});

class CustomResponderForOpaqueTokenResponderFactoryTest implements OpaqueTokenResponderInterface
{
    public function respond(OpaqueToken $opaqueToken): ResponseInterface
    {
        return Mockery::mock(ResponseInterface::class);
    }
}

class NotResponderForOpaqueTokenResponderFactoryTest
{
}
