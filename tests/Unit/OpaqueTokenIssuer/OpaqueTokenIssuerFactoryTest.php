<?php

declare(strict_types=1);

use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuer;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerInterface;
use GaaraHyperf\Token\TokenInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('creates default opaque token manager with default options', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenIssuerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenIssuerInterface::class);

    $container->shouldReceive('make')->once()->with(OpaqueTokenIssuer::class, [
        'prefix' => 'gaara:opaque_token:default',
        'idleTtl' => 1200,
        'maxTtl' => 86400,
        'tokenRefresh' => true,
        'singleSession' => true,
        'ipBindEnabled' => false,
        'userAgentBindEnabled' => false,
        'accessTokenLength' => 64,
    ])->andReturn($manager);

    $factory = new OpaqueTokenIssuerFactory($container);

    expect($factory->create([]))->toBe($manager);
});

it('creates default opaque token manager with custom options', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenIssuerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenIssuerInterface::class);

    $container->shouldReceive('make')->once()->with(OpaqueTokenIssuer::class, [
        'prefix' => 'gaara:opaque_token:api',
        'idleTtl' => 300,
        'maxTtl' => 3600,
        'tokenRefresh' => false,
        'singleSession' => false,
        'ipBindEnabled' => true,
        'userAgentBindEnabled' => true,
        'accessTokenLength' => 32,
    ])->andReturn($manager);

    $factory = new OpaqueTokenIssuerFactory($container);

    expect($factory->create([
        'type' => 'default',
        'prefix' => 'api',
        'idle_ttl' => 300,
        'max_ttl' => 3600,
        'token_refresh' => false,
        'single_session' => false,
        'ip_bind_enabled' => true,
        'user_agent_bind_enabled' => true,
        'access_token_length' => 32,
    ]))->toBe($manager);
});

it('creates custom opaque token manager and maps params', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenIssuerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenIssuerInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomOpaqueTokenManagerForFactoryTest::class,
        ['tokenLength' => 64]
    )->andReturn($manager);

    $factory = new OpaqueTokenIssuerFactory($container);

    expect($factory->create([
        'type' => 'custom',
        'class' => CustomOpaqueTokenManagerForFactoryTest::class,
        'params' => ['token_length' => 64],
    ]))->toBe($manager);
});

it('throws when custom opaque token manager does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotOpaqueTokenManagerForFactoryTest::class, [])->andReturn(new NotOpaqueTokenManagerForFactoryTest());

    $factory = new OpaqueTokenIssuerFactory($container);

    expect(fn () => $factory->create([
        'type' => 'custom',
        'class' => NotOpaqueTokenManagerForFactoryTest::class,
    ]))->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when opaque token manager type is unsupported', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new OpaqueTokenIssuerFactory($container);

    expect(fn () => $factory->create(['type' => 'unknown']))
        ->toThrow(InvalidArgumentException::class, 'Unsupported opaque token manager type');
});

class CustomOpaqueTokenManagerForFactoryTest implements OpaqueTokenIssuerInterface
{
    public function issue(TokenInterface $token): OpaqueToken
    {
        return new OpaqueToken($token, 'token', time(), 300);
    }

    public function resolve(string $accessToken): ?OpaqueToken
    {
        return null;
    }

    public function revoke(string $accessToken): void
    {
    }
}

class NotOpaqueTokenManagerForFactoryTest
{
}
