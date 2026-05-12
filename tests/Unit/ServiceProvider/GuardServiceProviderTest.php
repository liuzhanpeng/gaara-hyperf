<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorFactory;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authorization\AccessDeniedHandlerInterface;
use GaaraHyperf\Authorization\AuthorizationCheckerInterface;
use GaaraHyperf\Authorization\AuthorizationRuleResolverInterface;
use GaaraHyperf\Config\Config;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\Config\GuardConfig;
use GaaraHyperf\GuardInterface;
use GaaraHyperf\GuardResolver;
use GaaraHyperf\PasswordHasher\PasswordHasherInterface;
use GaaraHyperf\PasswordHasher\PasswordHasherResolverInterface;
use GaaraHyperf\RequestMatcher\RequestMatcherFactory;
use GaaraHyperf\RequestMatcher\RequestMatcherInterface;
use GaaraHyperf\ServiceProvider\GuardServiceProvider;
use GaaraHyperf\Token\TokenContextInterface;
use GaaraHyperf\TokenStorage\TokenStorageFactory;
use GaaraHyperf\TokenStorage\TokenStorageInterface;
use GaaraHyperf\UnauthenticatedHandler\UnauthenticatedHandlerFactory;
use GaaraHyperf\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use GaaraHyperf\UserProvider\UserProviderFactory;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('registers token context and guard resolver and resolves guard', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    /** @var ConfigLoaderInterface&MockInterface $loader */
    $loader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var MockInterface&RequestMatcherFactory $requestMatcherFactory */
    $requestMatcherFactory = Mockery::mock(RequestMatcherFactory::class);
    /** @var MockInterface&TokenStorageFactory $tokenStorageFactory */
    $tokenStorageFactory = Mockery::mock(TokenStorageFactory::class);
    /** @var MockInterface&UnauthenticatedHandlerFactory $unauthenticatedHandlerFactory */
    $unauthenticatedHandlerFactory = Mockery::mock(UnauthenticatedHandlerFactory::class);
    /** @var MockInterface&PasswordHasherResolverInterface $passwordHasherResolver */
    $passwordHasherResolver = Mockery::mock(PasswordHasherResolverInterface::class);
    /** @var MockInterface&UserProviderFactory $userProviderFactory */
    $userProviderFactory = Mockery::mock(UserProviderFactory::class);
    /** @var AuthenticatorFactory&MockInterface $authenticatorFactory */
    $authenticatorFactory = Mockery::mock(AuthenticatorFactory::class);

    /** @var MockInterface&RequestMatcherInterface $requestMatcher */
    $requestMatcher = Mockery::mock(RequestMatcherInterface::class);
    /** @var MockInterface&TokenStorageInterface $tokenStorage */
    $tokenStorage = Mockery::mock(TokenStorageInterface::class);
    /** @var MockInterface&UnauthenticatedHandlerInterface $unauthenticatedHandler */
    $unauthenticatedHandler = Mockery::mock(UnauthenticatedHandlerInterface::class);
    /** @var AuthorizationRuleResolverInterface&MockInterface $authorizationRuleResolver */
    $authorizationRuleResolver = Mockery::mock(AuthorizationRuleResolverInterface::class);
    /** @var AuthorizationCheckerInterface&MockInterface $authorizationChecker */
    $authorizationChecker = Mockery::mock(AuthorizationCheckerInterface::class);
    /** @var AccessDeniedHandlerInterface&MockInterface $accessDeniedHandler */
    $accessDeniedHandler = Mockery::mock(AccessDeniedHandlerInterface::class);
    /** @var MockInterface&PasswordHasherInterface $passwordHasher */
    $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);

    $guardConfig = GuardConfig::from([
        'matcher' => ['type' => 'default', 'pattern' => '/'],
        'user_provider' => ['type' => 'memory', 'users' => ['alice' => ['password' => 'secret']]],
        'authenticators' => ['api_key' => []],
    ]);

    $loader->shouldReceive('load')->once()->andReturn(new Config(['main' => $guardConfig], []));

    $requestMatcherFactory->shouldReceive('create')->once()->andReturn($requestMatcher);
    $tokenStorageFactory->shouldReceive('create')->once()->andReturn($tokenStorage);
    $unauthenticatedHandlerFactory->shouldReceive('create')->once()->andReturn($unauthenticatedHandler);
    $passwordHasherResolver->shouldReceive('resolve')->once()->with('default')->andReturn($passwordHasher);
    $userProviderFactory->shouldReceive('create')->once()->andReturn($userProvider);
    $authenticatorFactory->shouldReceive('create')->once()->andReturn($authenticator);

    $container->shouldReceive('get')->with(ConfigLoaderInterface::class)->andReturn($loader);
    $container->shouldReceive('get')->with(RequestMatcherFactory::class)->andReturn($requestMatcherFactory);
    $container->shouldReceive('get')->with(TokenStorageFactory::class)->andReturn($tokenStorageFactory);
    $container->shouldReceive('get')->with(UnauthenticatedHandlerFactory::class)->andReturn($unauthenticatedHandlerFactory);
    $container->shouldReceive('get')->with(PasswordHasherResolverInterface::class)->andReturn($passwordHasherResolver);
    $container->shouldReceive('get')->with(UserProviderFactory::class)->andReturn($userProviderFactory);
    $container->shouldReceive('get')->with(AuthenticatorFactory::class)->andReturn($authenticatorFactory);

    $tokenContext = null;
    $guardResolver = null;

    $container->shouldReceive('set')->twice()->withArgs(function (string $id, mixed $service) use (&$tokenContext, &$guardResolver): bool {
        if ($id === TokenContextInterface::class) {
            $tokenContext = $service;
            return true;
        }

        if ($id === GuardResolver::class) {
            $guardResolver = $service;
            return true;
        }

        return false;
    });

    $container->shouldReceive('get')->with(TokenContextInterface::class)->andReturnUsing(function () use (&$tokenContext) {
        return $tokenContext;
    });

    $container->shouldReceive('make')->andReturnUsing(function (string $class, array $params = []) use ($authorizationRuleResolver, $authorizationChecker, $accessDeniedHandler): mixed {
        if (str_contains($class, 'HttpAuthorizationRuleResolver')) {
            return $authorizationRuleResolver;
        }

        if (str_contains($class, 'NullAuthorizationChecker')) {
            return $authorizationChecker;
        }

        if (str_contains($class, 'DefaultAccessDeniedHandler')) {
            return $accessDeniedHandler;
        }

        throw new InvalidArgumentException('Unexpected make class: ' . $class);
    });

    $provider = new GuardServiceProvider();
    $provider->register($container);

    expect($tokenContext)->toBeInstanceOf(TokenContextInterface::class)
        ->and($guardResolver)->toBeInstanceOf(GuardResolver::class);

    if (! $guardResolver instanceof GuardResolver) {
        throw new InvalidArgumentException('guard resolver not registered');
    }

    $guard = $guardResolver->resolve('main');
    expect($guard)->toBeInstanceOf(GuardInterface::class)
        ->and($guard->name())->toBe('main');
});

it('throws when custom listener does not implement event subscriber interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    /** @var ConfigLoaderInterface&MockInterface $loader */
    $loader = Mockery::mock(ConfigLoaderInterface::class);
    /** @var MockInterface&RequestMatcherFactory $requestMatcherFactory */
    $requestMatcherFactory = Mockery::mock(RequestMatcherFactory::class);
    /** @var MockInterface&TokenStorageFactory $tokenStorageFactory */
    $tokenStorageFactory = Mockery::mock(TokenStorageFactory::class);
    /** @var MockInterface&UnauthenticatedHandlerFactory $unauthenticatedHandlerFactory */
    $unauthenticatedHandlerFactory = Mockery::mock(UnauthenticatedHandlerFactory::class);
    /** @var MockInterface&PasswordHasherResolverInterface $passwordHasherResolver */
    $passwordHasherResolver = Mockery::mock(PasswordHasherResolverInterface::class);
    /** @var MockInterface&UserProviderFactory $userProviderFactory */
    $userProviderFactory = Mockery::mock(UserProviderFactory::class);
    /** @var AuthenticatorFactory&MockInterface $authenticatorFactory */
    $authenticatorFactory = Mockery::mock(AuthenticatorFactory::class);

    /** @var MockInterface&RequestMatcherInterface $requestMatcher */
    $requestMatcher = Mockery::mock(RequestMatcherInterface::class);
    /** @var MockInterface&TokenStorageInterface $tokenStorage */
    $tokenStorage = Mockery::mock(TokenStorageInterface::class);
    /** @var MockInterface&UnauthenticatedHandlerInterface $unauthenticatedHandler */
    $unauthenticatedHandler = Mockery::mock(UnauthenticatedHandlerInterface::class);
    /** @var AuthorizationRuleResolverInterface&MockInterface $authorizationRuleResolver */
    $authorizationRuleResolver = Mockery::mock(AuthorizationRuleResolverInterface::class);
    /** @var AuthorizationCheckerInterface&MockInterface $authorizationChecker */
    $authorizationChecker = Mockery::mock(AuthorizationCheckerInterface::class);
    /** @var AccessDeniedHandlerInterface&MockInterface $accessDeniedHandler */
    $accessDeniedHandler = Mockery::mock(AccessDeniedHandlerInterface::class);
    /** @var MockInterface&PasswordHasherInterface $passwordHasher */
    $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);

    $guardConfig = GuardConfig::from([
        'matcher' => ['type' => 'default', 'pattern' => '/'],
        'user_provider' => ['type' => 'memory', 'users' => ['alice' => ['password' => 'secret']]],
        'authenticators' => ['api_key' => []],
        'listeners' => [
            ['class' => GuardServiceProviderTestInvalidListener::class],
        ],
    ]);

    $loader->shouldReceive('load')->once()->andReturn(new Config(['main' => $guardConfig], []));

    $requestMatcherFactory->shouldReceive('create')->once()->andReturn($requestMatcher);
    $tokenStorageFactory->shouldReceive('create')->once()->andReturn($tokenStorage);
    $unauthenticatedHandlerFactory->shouldReceive('create')->once()->andReturn($unauthenticatedHandler);
    $passwordHasherResolver->shouldReceive('resolve')->once()->with('default')->andReturn($passwordHasher);
    $userProviderFactory->shouldReceive('create')->once()->andReturn($userProvider);
    $authenticatorFactory->shouldReceive('create')->once()->andReturn($authenticator);

    $container->shouldReceive('get')->with(ConfigLoaderInterface::class)->andReturn($loader);
    $container->shouldReceive('get')->with(RequestMatcherFactory::class)->andReturn($requestMatcherFactory);
    $container->shouldReceive('get')->with(TokenStorageFactory::class)->andReturn($tokenStorageFactory);
    $container->shouldReceive('get')->with(UnauthenticatedHandlerFactory::class)->andReturn($unauthenticatedHandlerFactory);
    $container->shouldReceive('get')->with(PasswordHasherResolverInterface::class)->andReturn($passwordHasherResolver);
    $container->shouldReceive('get')->with(UserProviderFactory::class)->andReturn($userProviderFactory);
    $container->shouldReceive('get')->with(AuthenticatorFactory::class)->andReturn($authenticatorFactory);

    $tokenContext = null;
    $guardResolver = null;

    $container->shouldReceive('set')->twice()->withArgs(function (string $id, mixed $service) use (&$tokenContext, &$guardResolver): bool {
        if ($id === TokenContextInterface::class) {
            $tokenContext = $service;
            return true;
        }

        if ($id === GuardResolver::class) {
            $guardResolver = $service;
            return true;
        }

        return false;
    });

    $container->shouldReceive('get')->with(TokenContextInterface::class)->andReturnUsing(function () use (&$tokenContext) {
        return $tokenContext;
    });

    $container->shouldReceive('make')->andReturnUsing(function (string $class, array $params = []) use ($authorizationRuleResolver, $authorizationChecker, $accessDeniedHandler): mixed {
        if (str_contains($class, 'HttpAuthorizationRuleResolver')) {
            return $authorizationRuleResolver;
        }

        if (str_contains($class, 'NullAuthorizationChecker')) {
            return $authorizationChecker;
        }

        if (str_contains($class, 'DefaultAccessDeniedHandler')) {
            return $accessDeniedHandler;
        }

        if ($class === GuardServiceProviderTestInvalidListener::class) {
            return new GuardServiceProviderTestInvalidListener();
        }

        throw new InvalidArgumentException('Unexpected make class: ' . $class);
    });

    $provider = new GuardServiceProvider();
    $provider->register($container);

    if (! $guardResolver instanceof GuardResolver) {
        throw new InvalidArgumentException('guard resolver not registered');
    }

    expect(fn () => $guardResolver->resolve('main'))
        ->toThrow(InvalidArgumentException::class, 'must implement EventSubscriberInterface');
});

class GuardServiceProviderTestInvalidListener
{
}
