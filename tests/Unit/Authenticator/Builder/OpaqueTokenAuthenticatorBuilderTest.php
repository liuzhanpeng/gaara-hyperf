<?php

declare(strict_types=1);

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorFactory;
use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
use GaaraHyperf\Authenticator\Builder\OpaqueTokenAuthenticatorBuilder;
use GaaraHyperf\Authenticator\OpaqueTokenAuthenticator;
use GaaraHyperf\Event\LogoutEvent;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('creates authenticator with default token extractor config and registers logout listener', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var AccessTokenExtractorFactory&MockInterface $extractorFactory */
    $extractorFactory = Mockery::mock(AccessTokenExtractorFactory::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $resolver->shouldReceive('resolve')->once()->with('default')->andReturn($manager);
    $extractorFactory->shouldReceive('create')->once()->with([
        'type' => 'header',
        'field' => 'Authorization',
        'scheme' => 'Bearer',
    ])->andReturn($extractor);

    $container->shouldReceive('get')->once()->with(OpaqueTokenManagerResolverInterface::class)->andReturn($resolver);
    $container->shouldReceive('get')->once()->with(AccessTokenExtractorFactory::class)->andReturn($extractorFactory);

    $dispatcher = new EventDispatcher();
    $builder = new OpaqueTokenAuthenticatorBuilder($container);
    $authenticator = $builder->create([], $userProvider, $dispatcher);

    expect($authenticator)->toBeInstanceOf(OpaqueTokenAuthenticator::class);

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getMethod')->once()->andReturn('POST');
    $extractor->shouldReceive('extract')->once()->with($request)->andReturn('logout-token');
    $manager->shouldReceive('revoke')->once()->with('logout-token');

    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    $dispatcher->dispatch(new LogoutEvent($token, $request));
});

it('creates authenticator with custom token manager and extractor options', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&OpaqueTokenManagerResolverInterface $resolver */
    $resolver = Mockery::mock(OpaqueTokenManagerResolverInterface::class);
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var AccessTokenExtractorFactory&MockInterface $extractorFactory */
    $extractorFactory = Mockery::mock(AccessTokenExtractorFactory::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $resolver->shouldReceive('resolve')->once()->with('custom')->andReturn($manager);
    $extractorFactory->shouldReceive('create')->once()->with([
        'type' => 'cookie',
        'field' => 'my_token',
    ])->andReturn($extractor);

    $container->shouldReceive('get')->once()->with(OpaqueTokenManagerResolverInterface::class)->andReturn($resolver);
    $container->shouldReceive('get')->once()->with(AccessTokenExtractorFactory::class)->andReturn($extractorFactory);

    $dispatcher = new EventDispatcher();
    $builder = new OpaqueTokenAuthenticatorBuilder($container);
    $authenticator = $builder->create([
        'token_manager' => 'custom',
        'token_extractor' => [
            'type' => 'cookie',
            'field' => 'my_token',
        ],
    ], $userProvider, $dispatcher);

    expect($authenticator)->toBeInstanceOf(OpaqueTokenAuthenticator::class);
});
