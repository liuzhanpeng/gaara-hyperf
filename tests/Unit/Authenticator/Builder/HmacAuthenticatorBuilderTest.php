<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\Builder\HmacAuthenticatorBuilder;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('throws InvalidArgumentException when algo is unsupported', function (): void {
    $container = Mockery::mock(ContainerInterface::class);
    $builder = new HmacAuthenticatorBuilder($container);

    $userProvider = Mockery::mock(UserProviderInterface::class);

    expect(fn () => $builder->create([
        'algo' => 'invalid_algo',
    ], $userProvider, new EventDispatcher()))
        ->toThrow(InvalidArgumentException::class);
});
