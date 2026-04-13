<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\Builder\FormLoginAuthenticatorBuilder;
use GaaraHyperf\CsrfTokenManager\CsrfToken;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolverInterface;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\Passport\CsrfTokenBadge;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HyperfResponseInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('throws when check path option is missing', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $builder = new FormLoginAuthenticatorBuilder($container);

    expect(fn () => $builder->create([], $userProvider, new EventDispatcher()))
        ->toThrow(InvalidArgumentException::class, 'check_path');
});

it('creates authenticator when csrf is disabled', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $container->shouldReceive('get')->once()->with(HyperfResponseInterface::class)->andReturn($response);
    $container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);

    $builder = new FormLoginAuthenticatorBuilder($container);
    $authenticator = $builder->create([
        'check_path' => '/login',
        'csrf_enabled' => false,
    ], $userProvider, new EventDispatcher());

    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('getPath')->andReturn('/login');

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn('POST');

    expect($authenticator->supports($request))->toBeTrue();
});

it('registers csrf badge listener when csrf is enabled', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var HyperfResponseInterface&MockInterface $response */
    $response = Mockery::mock(HyperfResponseInterface::class);
    /** @var MockInterface&SessionInterface $session */
    $session = Mockery::mock(SessionInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var CsrfTokenManagerResolverInterface&MockInterface $resolver */
    $resolver = Mockery::mock(CsrfTokenManagerResolverInterface::class);
    /** @var CsrfTokenManagerInterface&MockInterface $manager */
    $manager = Mockery::mock(CsrfTokenManagerInterface::class);

    $resolver->shouldReceive('resolve')->once()->with('default')->andReturn($manager);
    $manager->shouldReceive('verify')->once()->with(Mockery::type(CsrfToken::class))->andReturnTrue();

    $container->shouldReceive('get')->once()->with(CsrfTokenManagerResolverInterface::class)->andReturn($resolver);
    $container->shouldReceive('get')->once()->with(HyperfResponseInterface::class)->andReturn($response);
    $container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);

    $dispatcher = new EventDispatcher();
    $builder = new FormLoginAuthenticatorBuilder($container);
    $builder->create([
        'check_path' => '/login',
        'csrf_enabled' => true,
        'csrf_id' => 'login-form',
    ], $userProvider, $dispatcher);

    $user = new class implements UserInterface {
        public function getIdentifier(): string
        {
            return 'u1';
        }
    };

    $passport = new Passport('u1', fn () => $user, [new CsrfTokenBadge('login-form', 'valid-csrf')]);

    /** @var AuthenticatorInterface&MockInterface $authenticator */
    $authenticator = Mockery::mock(AuthenticatorInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $dispatcher->dispatch(new CheckPassportEvent('web', $authenticator, $passport, $request));

    expect($passport->getBadge(CsrfTokenBadge::class)?->isResolved())->toBeTrue();
});
