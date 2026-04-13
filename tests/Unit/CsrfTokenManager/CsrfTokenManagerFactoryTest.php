<?php

declare(strict_types=1);

use GaaraHyperf\CsrfTokenManager\CsrfToken;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerFactory;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface;
use GaaraHyperf\CsrfTokenManager\SessionCsrfTokenManager;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('creates session csrf token manager with default prefix', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);

    $container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
    $container->shouldReceive('make')->once()->with(SessionCsrfTokenManager::class, [
        'prefix' => 'gaara.csrf_token.default',
        'session' => $session,
    ])->andReturn($manager);

    $factory = new CsrfTokenManagerFactory($container);

    expect($factory->create([]))->toBe($manager);
});

it('creates session csrf token manager with custom prefix', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);

    $container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
    $container->shouldReceive('make')->once()->with(SessionCsrfTokenManager::class, [
        'prefix' => 'gaara.csrf_token.login',
        'session' => $session,
    ])->andReturn($manager);

    $factory = new CsrfTokenManagerFactory($container);

    expect($factory->create(['type' => 'session', 'prefix' => 'login']))->toBe($manager);
});

it('creates custom csrf token manager and maps params', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomCsrfTokenManagerForFactoryTest::class,
        ['tokenTtl' => 3600]
    )->andReturn($manager);

    $factory = new CsrfTokenManagerFactory($container);

    expect($factory->create([
        'type' => 'custom',
        'class' => CustomCsrfTokenManagerForFactoryTest::class,
        'params' => ['token_ttl' => 3600],
    ]))->toBe($manager);
});

it('throws when custom csrf token manager does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotCsrfTokenManagerForFactoryTest::class, [])->andReturn(new NotCsrfTokenManagerForFactoryTest());

    $factory = new CsrfTokenManagerFactory($container);

    expect(fn () => $factory->create([
        'type' => 'custom',
        'class' => NotCsrfTokenManagerForFactoryTest::class,
    ]))->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when csrf token manager type is unsupported', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new CsrfTokenManagerFactory($container);

    expect(fn () => $factory->create(['type' => 'unknown']))
        ->toThrow(InvalidArgumentException::class, 'Unsupported CSRF Token Manager type');
});

class CustomCsrfTokenManagerForFactoryTest implements CsrfTokenManagerInterface
{
    public function generate(string $tokenId = 'authenticate'): CsrfToken
    {
        return new CsrfToken($tokenId, 'token');
    }

    public function verify(CsrfToken $token): bool
    {
        return true;
    }
}

class NotCsrfTokenManagerForFactoryTest
{
}
