<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\AuthenticationFailureHandlerInterface;
use GaaraHyperf\Authenticator\AuthenticationSuccessHandlerInterface;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\Builder\AbstractAuthenticatorBuilder;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('returns null handlers when options do not provide handler configs', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $builder = new AbstractAuthenticatorBuilderTestProbe($container);

    expect($builder->exposeCreateSuccessHandler([]))->toBeNull();
    expect($builder->exposeCreateFailureHandler([]))->toBeNull();
});

it('creates success handler from class string config', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AuthenticationSuccessHandlerInterface&MockInterface $handler */
    $handler = Mockery::mock(AuthenticationSuccessHandlerInterface::class);

    $container->shouldReceive('make')->once()->with(AbstractAuthenticatorBuilderTestSuccessHandler::class, [])->andReturn($handler);

    $builder = new AbstractAuthenticatorBuilderTestProbe($container);

    expect($builder->exposeCreateSuccessHandler([
        'success_handler' => AbstractAuthenticatorBuilderTestSuccessHandler::class,
    ]))->toBe($handler);
});

it('maps snake case params to camel case when creating success handler', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AuthenticationSuccessHandlerInterface&MockInterface $handler */
    $handler = Mockery::mock(AuthenticationSuccessHandlerInterface::class);

    $container->shouldReceive('make')->once()->with(
        AbstractAuthenticatorBuilderTestSuccessHandler::class,
        [
            'tokenManager' => 'default',
            'responseTemplate' => '{}',
        ]
    )->andReturn($handler);

    $builder = new AbstractAuthenticatorBuilderTestProbe($container);

    expect($builder->exposeCreateSuccessHandler([
        'success_handler' => [
            'class' => AbstractAuthenticatorBuilderTestSuccessHandler::class,
            'params' => [
                'token_manager' => 'default',
                'response_template' => '{}',
            ],
        ],
    ]))->toBe($handler);
});

it('throws when created success handler does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    $container->shouldReceive('make')->once()->with(AbstractAuthenticatorBuilderTestNotAHandler::class, [])->andReturn(new AbstractAuthenticatorBuilderTestNotAHandler());

    $builder = new AbstractAuthenticatorBuilderTestProbe($container);

    expect(fn () => $builder->exposeCreateSuccessHandler([
        'success_handler' => AbstractAuthenticatorBuilderTestNotAHandler::class,
    ]))->toThrow(InvalidArgumentException::class, 'must implement');
});

it('creates failure handler from string config and maps params', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var AuthenticationFailureHandlerInterface&MockInterface $handler */
    $handler = Mockery::mock(AuthenticationFailureHandlerInterface::class);

    $container->shouldReceive('make')->once()->with(
        AbstractAuthenticatorBuilderTestFailureHandler::class,
        ['errorMessage' => 'bad credentials']
    )->andReturn($handler);

    $builder = new AbstractAuthenticatorBuilderTestProbe($container);

    expect($builder->exposeCreateFailureHandler([
        'failure_handler' => [
            'class' => AbstractAuthenticatorBuilderTestFailureHandler::class,
            'params' => [
                'error_message' => 'bad credentials',
            ],
        ],
    ]))->toBe($handler);
});

it('throws when created failure handler does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    $container->shouldReceive('make')->once()->with(AbstractAuthenticatorBuilderTestNotAHandler::class, [])->andReturn(new AbstractAuthenticatorBuilderTestNotAHandler());

    $builder = new AbstractAuthenticatorBuilderTestProbe($container);

    expect(fn () => $builder->exposeCreateFailureHandler([
        'failure_handler' => AbstractAuthenticatorBuilderTestNotAHandler::class,
    ]))->toThrow(InvalidArgumentException::class, 'must implement');
});

class AbstractAuthenticatorBuilderTestProbe extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        throw new RuntimeException('not used in this test');
    }

    public function exposeCreateSuccessHandler(array $options): ?AuthenticationSuccessHandlerInterface
    {
        return $this->createSuccessHandler($options);
    }

    public function exposeCreateFailureHandler(array $options): ?AuthenticationFailureHandlerInterface
    {
        return $this->createFailureHandler($options);
    }
}

class AbstractAuthenticatorBuilderTestSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function handle(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        return null;
    }
}

class AbstractAuthenticatorBuilderTestFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function handle(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ResponseInterface
    {
        throw new RuntimeException('not used in this test');
    }
}

class AbstractAuthenticatorBuilderTestNotAHandler
{
}
