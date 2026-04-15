<?php

declare(strict_types=1);

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorFactory;
use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\Config\Config;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerResolver;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerResolverInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManager;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolver;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderFactory;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder\OpaqueTokenResponderInterface;
use GaaraHyperf\ServiceProvider\OpaqueTokenManagerServiceProvider;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers resolvers and lazily creates the default opaque token manager', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var ConfigLoaderInterface&MockInterface $loader */
    $loader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var MockInterface&OpaqueTokenIssuerFactory $issuerFactory */
    $issuerFactory = Mockery::mock(OpaqueTokenIssuerFactory::class);
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);
    /** @var AccessTokenExtractorFactory&MockInterface $extractorFactory */
    $extractorFactory = Mockery::mock(AccessTokenExtractorFactory::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&OpaqueTokenResponderFactory $responderFactory */
    $responderFactory = Mockery::mock(OpaqueTokenResponderFactory::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);

    $loader->shouldReceive('load')->once()->andReturn(new Config([], []));
    $container->shouldReceive('get')->once()->with(ConfigLoaderInterface::class)->andReturn($loader);

    $capturedIssuerResolver = null;
    $capturedManagerResolver = null;

    $container->shouldReceive('set')->once()->with(
        OpaqueTokenIssuerResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedIssuerResolver): bool {
            $capturedIssuerResolver = $resolver;
            return $resolver instanceof OpaqueTokenIssuerResolver;
        })
    );

    $container->shouldReceive('set')->once()->with(
        OpaqueTokenManagerResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedManagerResolver): bool {
            $capturedManagerResolver = $resolver;
            return $resolver instanceof OpaqueTokenManagerResolver;
        })
    );

    (new OpaqueTokenManagerServiceProvider())->register($container);

    $container->shouldReceive('get')->once()->with(OpaqueTokenIssuerFactory::class)->andReturn($issuerFactory);
    $container->shouldReceive('get')->once()->with(AccessTokenExtractorFactory::class)->andReturn($extractorFactory);
    $container->shouldReceive('get')->once()->with(OpaqueTokenResponderFactory::class)->andReturn($responderFactory);
    $container->shouldReceive('get')->once()->with(OpaqueTokenIssuerResolverInterface::class)->andReturn($capturedIssuerResolver);

    $issuerFactory->shouldReceive('create')->once()->with(Mockery::on(function (array $config): bool {
        return $config['type'] === 'default'
            && $config['prefix'] === 'default'
            && $config['idle_ttl'] === 1200
            && $config['max_ttl'] === 86400;
    }))->andReturn($issuer);

    $extractorFactory->shouldReceive('create')->once()->with([
        'type' => 'header',
        'field' => 'Authorization',
        'scheme' => 'Bearer',
    ])->andReturn($extractor);

    $responderFactory->shouldReceive('create')->once()->with(Mockery::on(function (array $config): bool {
        return $config['type'] === 'body'
            && str_contains($config['template'], '#ACCESS_TOKEN#')
            && str_contains($config['template'], '#USER_IDENTIFIER#');
    }))->andReturn($responder);

    $first = $capturedManagerResolver->resolve();
    $second = $capturedManagerResolver->resolve('default');

    expect($capturedIssuerResolver)->toBeInstanceOf(OpaqueTokenIssuerResolver::class)
        ->and($capturedManagerResolver)->toBeInstanceOf(OpaqueTokenManagerResolver::class)
        ->and($first)->toBeInstanceOf(OpaqueTokenManager::class)
        ->and($second)->toBe($first);
});

it('creates a named opaque token manager from custom config', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var ConfigLoaderInterface&MockInterface $loader */
    $loader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var MockInterface&OpaqueTokenIssuerFactory $issuerFactory */
    $issuerFactory = Mockery::mock(OpaqueTokenIssuerFactory::class);
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);
    /** @var AccessTokenExtractorFactory&MockInterface $extractorFactory */
    $extractorFactory = Mockery::mock(AccessTokenExtractorFactory::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&OpaqueTokenResponderFactory $responderFactory */
    $responderFactory = Mockery::mock(OpaqueTokenResponderFactory::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);

    $loader->shouldReceive('load')->once()->andReturn(new Config([], [
        'opaque_token_managers' => [
            'api' => [
                'type' => 'default',
                'prefix' => 'api',
                'token_extractor' => ['type' => 'cookie', 'field' => 'opaque_token'],
                'token_responder' => ['type' => 'body', 'template' => '{"ok":true}'],
            ],
        ],
    ]));

    $container->shouldReceive('get')->once()->with(ConfigLoaderInterface::class)->andReturn($loader);

    $capturedIssuerResolver = null;
    $capturedManagerResolver = null;

    $container->shouldReceive('set')->once()->with(
        OpaqueTokenIssuerResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedIssuerResolver): bool {
            $capturedIssuerResolver = $resolver;
            return $resolver instanceof OpaqueTokenIssuerResolver;
        })
    );

    $container->shouldReceive('set')->once()->with(
        OpaqueTokenManagerResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedManagerResolver): bool {
            $capturedManagerResolver = $resolver;
            return $resolver instanceof OpaqueTokenManagerResolver;
        })
    );

    (new OpaqueTokenManagerServiceProvider())->register($container);

    $container->shouldReceive('get')->once()->with(OpaqueTokenIssuerFactory::class)->andReturn($issuerFactory);
    $container->shouldReceive('get')->once()->with(AccessTokenExtractorFactory::class)->andReturn($extractorFactory);
    $container->shouldReceive('get')->once()->with(OpaqueTokenResponderFactory::class)->andReturn($responderFactory);
    $container->shouldReceive('get')->once()->with(OpaqueTokenIssuerResolverInterface::class)->andReturn($capturedIssuerResolver);

    $issuerFactory->shouldReceive('create')->once()->with(Mockery::on(function (array $config): bool {
        return $config['prefix'] === 'api';
    }))->andReturn($issuer);

    $extractorFactory->shouldReceive('create')->once()->with([
        'type' => 'cookie',
        'field' => 'opaque_token',
        'scheme' => 'Bearer',
    ])->andReturn($extractor);

    $responderFactory->shouldReceive('create')->once()->with([
        'type' => 'body',
        'template' => '{"ok":true}',
    ])->andReturn($responder);

    $manager = $capturedManagerResolver->resolve('api');

    expect($manager)->toBeInstanceOf(OpaqueTokenManager::class);
});

it('applies default extractor and responder config when a manager only defines basic options', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var ConfigLoaderInterface&MockInterface $loader */
    $loader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var MockInterface&OpaqueTokenIssuerFactory $issuerFactory */
    $issuerFactory = Mockery::mock(OpaqueTokenIssuerFactory::class);
    /** @var MockInterface&OpaqueTokenIssuerInterface $issuer */
    $issuer = Mockery::mock(OpaqueTokenIssuerInterface::class);
    /** @var AccessTokenExtractorFactory&MockInterface $extractorFactory */
    $extractorFactory = Mockery::mock(AccessTokenExtractorFactory::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&OpaqueTokenResponderFactory $responderFactory */
    $responderFactory = Mockery::mock(OpaqueTokenResponderFactory::class);
    /** @var MockInterface&OpaqueTokenResponderInterface $responder */
    $responder = Mockery::mock(OpaqueTokenResponderInterface::class);

    $loader->shouldReceive('load')->once()->andReturn(new Config([], [
        'opaque_token_managers' => [
            'api' => [
                'type' => 'default',
                'prefix' => 'api',
            ],
        ],
    ]));

    $container->shouldReceive('get')->once()->with(ConfigLoaderInterface::class)->andReturn($loader);

    $capturedIssuerResolver = null;
    $capturedManagerResolver = null;

    $container->shouldReceive('set')->once()->with(
        OpaqueTokenIssuerResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedIssuerResolver): bool {
            $capturedIssuerResolver = $resolver;
            return $resolver instanceof OpaqueTokenIssuerResolver;
        })
    );

    $container->shouldReceive('set')->once()->with(
        OpaqueTokenManagerResolverInterface::class,
        Mockery::on(function (mixed $resolver) use (&$capturedManagerResolver): bool {
            $capturedManagerResolver = $resolver;
            return $resolver instanceof OpaqueTokenManagerResolver;
        })
    );

    (new OpaqueTokenManagerServiceProvider())->register($container);

    $container->shouldReceive('get')->once()->with(OpaqueTokenIssuerFactory::class)->andReturn($issuerFactory);
    $container->shouldReceive('get')->once()->with(AccessTokenExtractorFactory::class)->andReturn($extractorFactory);
    $container->shouldReceive('get')->once()->with(OpaqueTokenResponderFactory::class)->andReturn($responderFactory);
    $container->shouldReceive('get')->once()->with(OpaqueTokenIssuerResolverInterface::class)->andReturn($capturedIssuerResolver);

    $issuerFactory->shouldReceive('create')->once()->with(Mockery::on(function (array $config): bool {
        return $config['prefix'] === 'api';
    }))->andReturn($issuer);

    $extractorFactory->shouldReceive('create')->once()->with([
        'type' => 'header',
        'field' => 'Authorization',
        'scheme' => 'Bearer',
    ])->andReturn($extractor);

    $responderFactory->shouldReceive('create')->once()->with(Mockery::on(function (array $config): bool {
        return $config['type'] === 'body'
            && str_contains($config['template'], '#ACCESS_TOKEN#')
            && str_contains($config['template'], '#USER_IDENTIFIER#');
    }))->andReturn($responder);

    $manager = $capturedManagerResolver->resolve('api');

    expect($manager)->toBeInstanceOf(OpaqueTokenManager::class);
});
