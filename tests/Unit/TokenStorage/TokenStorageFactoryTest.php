<?php

declare(strict_types=1);

use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\TokenStorage\NullTokenStorage;
use GaaraHyperf\TokenStorage\SessionTokenStorage;
use GaaraHyperf\TokenStorage\TokenStorageFactory;
use GaaraHyperf\TokenStorage\TokenStorageInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('creates session token storage with prefixed namespace', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&TokenStorageInterface $storage */
    $storage = Mockery::mock(TokenStorageInterface::class);

    $container->shouldReceive('make')->once()->with(SessionTokenStorage::class, [
        'prefix' => 'gaara:token_storage:web',
    ])->andReturn($storage);

    $factory = new TokenStorageFactory($container);

    $config = new ComponentConfig('session', ['prefix' => 'web']);
    expect($factory->create($config))->toBe($storage);
});

it('throws when session prefix is missing or invalid', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new TokenStorageFactory($container);

    expect(fn () => $factory->create(new ComponentConfig('session', [])))
        ->toThrow(InvalidArgumentException::class, 'prefix');

    expect(fn () => $factory->create(new ComponentConfig('session', ['prefix' => 123])))
        ->toThrow(InvalidArgumentException::class, 'prefix');
});

it('creates null token storage', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&TokenStorageInterface $storage */
    $storage = Mockery::mock(TokenStorageInterface::class);

    $container->shouldReceive('make')->once()->with(NullTokenStorage::class)->andReturn($storage);

    $factory = new TokenStorageFactory($container);

    expect($factory->create(new ComponentConfig('null', [])))->toBe($storage);
});

it('creates custom token storage and maps params to camel case', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&TokenStorageInterface $storage */
    $storage = Mockery::mock(TokenStorageInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomTokenStorageForFactoryTest::class,
        ['storagePrefix' => 'api']
    )->andReturn($storage);

    $factory = new TokenStorageFactory($container);
    $config = new ComponentConfig('custom', [
        'class' => CustomTokenStorageForFactoryTest::class,
        'params' => ['storage_prefix' => 'api'],
    ]);

    expect($factory->create($config))->toBe($storage);
});

it('throws when custom token storage does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotTokenStorageForFactoryTest::class, [])->andReturn(new NotTokenStorageForFactoryTest());

    $factory = new TokenStorageFactory($container);
    $config = new ComponentConfig('custom', ['class' => NotTokenStorageForFactoryTest::class]);

    expect(fn () => $factory->create($config))
        ->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when token storage type is unsupported', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new TokenStorageFactory($container);

    expect(fn () => $factory->create(new ComponentConfig('unsupported', [])))
        ->toThrow(InvalidArgumentException::class, 'Unsupported token storage type');
});

class CustomTokenStorageForFactoryTest implements TokenStorageInterface
{
    public function get(string $key): ?TokenInterface
    {
        return null;
    }

    public function set(string $key, TokenInterface $token)
    {
        return null;
    }

    public function delete(string $key): void
    {
    }
}

class NotTokenStorageForFactoryTest
{
}
