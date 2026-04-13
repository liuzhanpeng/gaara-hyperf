<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\Builder\X509AuthenticatorBuilder;
use GaaraHyperf\Authenticator\X509Authenticator;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

afterEach(function (): void {
    Mockery::close();
});

it('creates x509 authenticator with default options', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);

    $builder = new X509AuthenticatorBuilder($container);
    $authenticator = $builder->create([], $userProvider, new EventDispatcher());

    $request = createX509AuthenticatorBuilderTestRequest('SSL_CLIENT_S_DN', '/C=CN/CN=Alice/emailAddress=alice@example.com');

    expect($authenticator)->toBeInstanceOf(X509Authenticator::class)
        ->and($authenticator->supports($request))->toBeTrue();
});

it('creates x509 authenticator with custom fields', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $identifier = 'Alice';
    $user = new class($identifier) implements UserInterface {
        public function __construct(private string $id)
        {
        }

        public function getIdentifier(): string
        {
            return $this->id;
        }
    };

    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    $userProvider->shouldReceive('findByIdentifier')->once()->with($identifier)->andReturn($user);

    $builder = new X509AuthenticatorBuilder($container);
    $authenticator = $builder->create([
        'ssl_client_s_dn_field' => 'X-CLIENT-DN',
        'identifier_field' => 'CN',
    ], $userProvider, new EventDispatcher());

    $request = createX509AuthenticatorBuilderTestRequest('X-CLIENT-DN', 'emailAddress=alice@example.com,CN=Alice,C=CN');

    $passport = $authenticator->authenticate($request);

    expect($passport->getUserIdentifier())->toBe('Alice')
        ->and($passport->getUser()->getIdentifier())->toBe('Alice');
});

function createX509AuthenticatorBuilderTestRequest(string $headerField, string $dn): ServerRequestInterface
{
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getHeaderLine')->once()->with($headerField)->andReturn($dn);

    return $request;
}
