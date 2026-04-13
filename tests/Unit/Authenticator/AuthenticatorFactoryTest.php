<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorBuilderInterface;
use GaaraHyperf\Authenticator\AuthenticatorFactory;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Config\AuthenticatorConfig;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('registers builder and creates authenticator via registered type', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AuthenticatorBuilderInterface&MockInterface $builder */
    $builder = Mockery::mock(AuthenticatorBuilderInterface::class);
    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $eventDispatcher = new EventDispatcher();
    $config = new AuthenticatorConfig('test_type', ['foo' => 'bar']);

    $container->shouldReceive('get')->once()->with(TestRegisteredBuilder::class)->andReturn($builder);
    $builder->shouldReceive('create')->once()->with(['foo' => 'bar'], $userProvider, $eventDispatcher)->andReturn($authenticator);

    $factory = new AuthenticatorFactory($container);
    $factory->registerBuilder('test_type', TestRegisteredBuilder::class);

    expect($factory->create($config, $userProvider, $eventDispatcher))->toBe($authenticator);
});

it('creates authenticator via class-string builder', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AuthenticatorBuilderInterface&MockInterface $builder */
    $builder = Mockery::mock(AuthenticatorBuilderInterface::class);
    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $eventDispatcher = new EventDispatcher();
    $config = new AuthenticatorConfig(ClassStringBuilderProbe::class, ['alpha' => 1]);

    $container->shouldReceive('make')->once()->with(ClassStringBuilderProbe::class, ['alpha' => 1])->andReturn($builder);
    $builder->shouldReceive('create')->once()->with(['alpha' => 1], $userProvider, $eventDispatcher)->andReturn($authenticator);

    $factory = new AuthenticatorFactory($container);

    expect($factory->create($config, $userProvider, $eventDispatcher))->toBe($authenticator);
});

it('throws exception when registering non-builder class', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new AuthenticatorFactory($container);

    expect(fn () => $factory->registerBuilder('bad', NotABuilder::class))
        ->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws exception when class-string resolved object is not builder', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $eventDispatcher = new EventDispatcher();
    $config = new AuthenticatorConfig(ClassStringBuilderProbe::class, ['x' => 'y']);

    $container->shouldReceive('make')->once()->with(ClassStringBuilderProbe::class, ['x' => 'y'])->andReturn(new NotABuilder());

    $factory = new AuthenticatorFactory($container);

    expect(fn () => $factory->create($config, $userProvider, $eventDispatcher))
        ->toThrow(InvalidArgumentException::class, 'must implement AuthenticatorBuilderInterface');
});

it('throws exception for unsupported authenticator type', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $eventDispatcher = new EventDispatcher();
    $config = new AuthenticatorConfig('unsupported_type', []);

    $factory = new AuthenticatorFactory($container);

    expect(fn () => $factory->create($config, $userProvider, $eventDispatcher))
        ->toThrow(InvalidArgumentException::class, 'Unsupported authenticator type');
});

it('prefers registered builder mapping over class-string resolution', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AuthenticatorBuilderInterface&MockInterface $builder */
    $builder = Mockery::mock(AuthenticatorBuilderInterface::class);
    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $eventDispatcher = new EventDispatcher();
    $config = new AuthenticatorConfig(ClassStringBuilderProbe::class, ['foo' => 'bar']);

    $container->shouldReceive('get')->once()->with(TestRegisteredBuilder::class)->andReturn($builder);
    $container->shouldReceive('make')->never();
    $builder->shouldReceive('create')->once()->with(['foo' => 'bar'], $userProvider, $eventDispatcher)->andReturn($authenticator);

    $factory = new AuthenticatorFactory($container);
    $factory->registerBuilder(ClassStringBuilderProbe::class, TestRegisteredBuilder::class);

    expect($factory->create($config, $userProvider, $eventDispatcher))->toBe($authenticator);
});

class TestRegisteredBuilder implements AuthenticatorBuilderInterface
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        throw new RuntimeException('Test double should be mocked via container->get().');
    }
}

class ClassStringBuilderProbe
{
}

class NotABuilder
{
}
