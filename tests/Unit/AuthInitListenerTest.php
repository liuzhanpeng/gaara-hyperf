<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorFactory;
use GaaraHyperf\AuthInitListener;
use GaaraHyperf\Config\Config;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerFactory;
use GaaraHyperf\PasswordHasher\PasswordHasherFactory;
use GaaraHyperf\ServiceProvider\ServiceProviderRegisterEvent;
use GaaraHyperf\UserProvider\UserProviderFactory;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Mockery\MockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

it('listens to before main server start event', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $listener = new AuthInitListener($container);

    expect($listener->listen())->toBe([BeforeMainServerStart::class]);
});

it('initializes auth during process call', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var EventDispatcherInterface&MockInterface $eventDispatcher */
    $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
    /** @var ConfigLoaderInterface&MockInterface $configLoader */
    $configLoader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var AuthenticatorFactory&MockInterface $authenticatorFactory */
    $authenticatorFactory = Mockery::mock(AuthenticatorFactory::class);
    /** @var MockInterface&UserProviderFactory $userProviderFactory */
    $userProviderFactory = Mockery::mock(UserProviderFactory::class);
    /** @var MockInterface&PasswordHasherFactory $passwordHasherFactory */
    $passwordHasherFactory = Mockery::mock(PasswordHasherFactory::class);
    /** @var CsrfTokenManagerFactory&MockInterface $csrfTokenManagerFactory */
    $csrfTokenManagerFactory = Mockery::mock(CsrfTokenManagerFactory::class);
    /** @var MockInterface&OpaqueTokenManagerFactory $opaqueTokenManagerFactory */
    $opaqueTokenManagerFactory = Mockery::mock(OpaqueTokenManagerFactory::class);

    $configLoader->shouldReceive('load')->andReturn(new Config([], []));
    $authenticatorFactory->shouldReceive('registerBuilder')->times(6);
    $userProviderFactory->shouldReceive('registerBuilder')->times(2);
    $passwordHasherFactory->shouldNotReceive('create');
    $csrfTokenManagerFactory->shouldNotReceive('create');
    $opaqueTokenManagerFactory->shouldNotReceive('create');

    $services = [
        EventDispatcherInterface::class => $eventDispatcher,
        ConfigLoaderInterface::class => $configLoader,
        AuthenticatorFactory::class => $authenticatorFactory,
        UserProviderFactory::class => $userProviderFactory,
        PasswordHasherFactory::class => $passwordHasherFactory,
        CsrfTokenManagerFactory::class => $csrfTokenManagerFactory,
        OpaqueTokenManagerFactory::class => $opaqueTokenManagerFactory,
    ];

    $container->shouldReceive('get')->andReturnUsing(function (string $id) use (&$services): mixed {
        return $services[$id] ?? null;
    });
    $container->shouldReceive('set')->andReturnUsing(function (string $id, mixed $service) use (&$services): void {
        $services[$id] = $service;
    });
    $container->shouldReceive('make')->andReturnUsing(function (string $class): mixed {
        return new $class();
    });
    $eventDispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::on(function (object $event): bool {
            return $event instanceof ServiceProviderRegisterEvent;
        }))
        ->andReturnUsing(fn (ServiceProviderRegisterEvent $event) => $event);

    $listener = new AuthInitListener($container);
    $listener->process(new BeforeMainServerStart(Mockery::mock('Swoole\Server'), []));

    expect(true)->toBeTrue();
});
