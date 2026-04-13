<?php

declare(strict_types=1);

use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\UnauthenticatedHandler\DefaultUnauthenticatedHandler;
use GaaraHyperf\UnauthenticatedHandler\RedirectUnauthenticatedHandler;
use GaaraHyperf\UnauthenticatedHandler\UnauthenticatedHandlerFactory;
use GaaraHyperf\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('creates default unauthenticated handler', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new UnauthenticatedHandlerFactory($container);

    expect($factory->create(new ComponentConfig('default', [])))->toBeInstanceOf(DefaultUnauthenticatedHandler::class);
});

it('creates redirect unauthenticated handler with default options', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UnauthenticatedHandlerInterface $handler */
    $handler = Mockery::mock(UnauthenticatedHandlerInterface::class);

    $container->shouldReceive('make')->once()->with(RedirectUnauthenticatedHandler::class, [
        'targetPath' => '',
        'redirectEnabled' => true,
        'redirectField' => 'redirect_to',
        'errorField' => 'authentication_error',
        'errorMessage' => '未认证或已登出，请重新登录',
    ])->andReturn($handler);

    $factory = new UnauthenticatedHandlerFactory($container);

    expect($factory->create(new ComponentConfig('redirect', [])))->toBe($handler);
});

it('creates custom unauthenticated handler and maps params', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UnauthenticatedHandlerInterface $handler */
    $handler = Mockery::mock(UnauthenticatedHandlerInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomUnauthenticatedHandlerForFactoryTest::class,
        ['errorMessage' => 'please login']
    )->andReturn($handler);

    $factory = new UnauthenticatedHandlerFactory($container);
    $config = new ComponentConfig('custom', [
        'class' => CustomUnauthenticatedHandlerForFactoryTest::class,
        'params' => ['error_message' => 'please login'],
    ]);

    expect($factory->create($config))->toBe($handler);
});

it('throws when custom unauthenticated handler does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotUnauthenticatedHandlerForFactoryTest::class, [])->andReturn(new NotUnauthenticatedHandlerForFactoryTest());

    $factory = new UnauthenticatedHandlerFactory($container);
    $config = new ComponentConfig('custom', ['class' => NotUnauthenticatedHandlerForFactoryTest::class]);

    expect(fn () => $factory->create($config))->toThrow(InvalidArgumentException::class, 'must implement');
});

it('throws when unauthenticated handler type is unsupported', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $factory = new UnauthenticatedHandlerFactory($container);

    expect(fn () => $factory->create(new ComponentConfig('unknown', [])))
        ->toThrow(InvalidArgumentException::class, 'Unsupported unauthenticated handler type');
});

class CustomUnauthenticatedHandlerForFactoryTest implements UnauthenticatedHandlerInterface
{
    public function handle(ServerRequestInterface $request, ?TokenInterface $token): ResponseInterface
    {
        throw new RuntimeException('not used');
    }
}

class NotUnauthenticatedHandlerForFactoryTest
{
}
