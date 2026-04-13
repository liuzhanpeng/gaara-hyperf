<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorFactory;
use GaaraHyperf\Authenticator\Builder\APIKeyAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\FormLoginAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\HmacAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\JsonLoginAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\OpaqueTokenAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\X509AuthenticatorBuilder;
use GaaraHyperf\ServiceProvider\BuiltInAuthenticatorServiceProvider;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers all built-in authenticator builders', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AuthenticatorFactory&MockInterface $factory */
    $factory = Mockery::mock(AuthenticatorFactory::class);

    $container->shouldReceive('get')->once()->with(AuthenticatorFactory::class)->andReturn($factory);

    $factory->shouldReceive('registerBuilder')->once()->with('form_login', FormLoginAuthenticatorBuilder::class);
    $factory->shouldReceive('registerBuilder')->once()->with('json_login', JsonLoginAuthenticatorBuilder::class);
    $factory->shouldReceive('registerBuilder')->once()->with('api_key', APIKeyAuthenticatorBuilder::class);
    $factory->shouldReceive('registerBuilder')->once()->with('hmac', HmacAuthenticatorBuilder::class);
    $factory->shouldReceive('registerBuilder')->once()->with('opaque_token', OpaqueTokenAuthenticatorBuilder::class);
    $factory->shouldReceive('registerBuilder')->once()->with('x509', X509AuthenticatorBuilder::class);

    $provider = new BuiltInAuthenticatorServiceProvider();
    $provider->register($container);

    expect(true)->toBeTrue();
});
