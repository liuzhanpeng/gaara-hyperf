<?php

declare(strict_types=1);

use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\RequestMatcher\RequestMatcher;
use GaaraHyperf\RequestMatcher\RequestMatcherFactory;
use GaaraHyperf\RequestMatcher\RequestMatcherInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('creates default request matcher with required pattern', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&RequestMatcherInterface $matcher */
    $matcher = Mockery::mock(RequestMatcherInterface::class);

    $container->shouldReceive('make')->once()->with(RequestMatcher::class, [
        'pattern' => '/api/*',
        'logoutPath' => '/logout',
        'exclusions' => ['/health'],
    ])->andReturn($matcher);

    $factory = new RequestMatcherFactory($container);

    $config = new ComponentConfig('default', [
        'pattern' => '/api/*',
        'logout_path' => '/logout',
        'exclusions' => ['/health'],
    ]);

    expect($factory->create($config))->toBe($matcher);
});

it('throws when default matcher pattern is missing', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new RequestMatcherFactory($container);

    expect(fn () => $factory->create(new ComponentConfig('default', [])))
        ->toThrow(InvalidArgumentException::class, 'pattern option is required');
});

it('creates custom matcher and maps params to camel case', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&RequestMatcherInterface $matcher */
    $matcher = Mockery::mock(RequestMatcherInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomRequestMatcherForFactoryTest::class,
        ['patternList' => ['/api/*']]
    )->andReturn($matcher);

    $factory = new RequestMatcherFactory($container);
    $config = new ComponentConfig('custom', [
        'class' => CustomRequestMatcherForFactoryTest::class,
        'params' => ['pattern_list' => ['/api/*']],
    ]);

    expect($factory->create($config))->toBe($matcher);
});

it('throws when custom matcher does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotMatcherForFactoryTest::class, [])->andReturn(new NotMatcherForFactoryTest());

    $factory = new RequestMatcherFactory($container);
    $config = new ComponentConfig('custom', ['class' => NotMatcherForFactoryTest::class]);

    expect(fn () => $factory->create($config))
        ->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when matcher type is unsupported', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new RequestMatcherFactory($container);

    expect(fn () => $factory->create(new ComponentConfig('unknown', [])))
        ->toThrow(InvalidArgumentException::class, 'Unsupported request matcher type');
});

class CustomRequestMatcherForFactoryTest implements RequestMatcherInterface
{
    public function matchesPattern(ServerRequestInterface $request): bool
    {
        return false;
    }

    public function matchesLogout(ServerRequestInterface $request): bool
    {
        return false;
    }

    public function matchesExcluded(ServerRequestInterface $request): bool
    {
        return false;
    }
}

class NotMatcherForFactoryTest
{
}
