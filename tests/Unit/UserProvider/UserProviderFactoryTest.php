<?php

declare(strict_types=1);

use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderBuilderInterface;
use GaaraHyperf\UserProvider\UserProviderFactory;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers builder and creates user provider from registered builder', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderBuilderInterface $builder */
    $builder = Mockery::mock(UserProviderBuilderInterface::class);
    /** @var MockInterface&UserProviderInterface $provider */
    $provider = Mockery::mock(UserProviderInterface::class);

    $container->shouldReceive('get')->once()->with(TestUserProviderBuilderForFactoryTest::class)->andReturn($builder);
    $builder->shouldReceive('create')->once()->with(['users' => []])->andReturn($provider);

    $factory = new UserProviderFactory($container);
    $factory->registerBuilder('memory', TestUserProviderBuilderForFactoryTest::class);

    $config = new ComponentConfig('memory', ['users' => []]);
    expect($factory->create($config))->toBe($provider);
});

it('creates custom user provider and maps params to camel case', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $provider */
    $provider = Mockery::mock(UserProviderInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomUserProviderForFactoryTest::class,
        ['userTable' => 'users']
    )->andReturn($provider);

    $factory = new UserProviderFactory($container);
    $config = new ComponentConfig('custom', [
        'class' => CustomUserProviderForFactoryTest::class,
        'params' => ['user_table' => 'users'],
    ]);

    expect($factory->create($config))->toBe($provider);
});

it('throws when registering invalid builder class', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    $factory = new UserProviderFactory($container);

    expect(fn () => $factory->registerBuilder('bad', InvalidBuilderForFactoryTest::class))
        ->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when custom user provider does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotUserProviderForFactoryTest::class, [])->andReturn(new NotUserProviderForFactoryTest());

    $factory = new UserProviderFactory($container);
    $config = new ComponentConfig('custom', ['class' => NotUserProviderForFactoryTest::class]);

    expect(fn () => $factory->create($config))->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when user provider type is unsupported', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    $factory = new UserProviderFactory($container);

    expect(fn () => $factory->create(new ComponentConfig('unknown', [])))
        ->toThrow(InvalidArgumentException::class, 'Unsupported user provider type');
});

class TestUserProviderBuilderForFactoryTest implements UserProviderBuilderInterface
{
    public function create(array $options): UserProviderInterface
    {
        throw new RuntimeException('not used');
    }
}

class CustomUserProviderForFactoryTest implements UserProviderInterface
{
    public function findByIdentifier(string $identifier): ?UserInterface
    {
        return null;
    }
}

class NotUserProviderForFactoryTest
{
}

class InvalidBuilderForFactoryTest
{
}
