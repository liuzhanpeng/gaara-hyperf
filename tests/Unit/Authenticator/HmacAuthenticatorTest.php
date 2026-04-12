<?php

declare(strict_types=1);

use GaaraHyperf\Authenticator\HmacAuthenticator;
use GaaraHyperf\Exception\InvalidSignatureException;
use GaaraHyperf\Exception\SignatureExpiredException;
use GaaraHyperf\Exception\UsedNonceException;
use GaaraHyperf\User\PasswordAwareUserInterface;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\CacheInterface;

afterEach(function (): void {
    Mockery::close();
});

it('authenticates request with valid signature', function (): void {
    $apiKey = 'client_a';
    $timestamp = (string) time();
    $method = 'POST';
    $path = '/api/resource';
    $query = ['b' => '2', 'a' => '1'];
    $body = '{"k":"v"}';
    $secret = 'top_secret';

    $request = createRequestMock(
        apiKey: $apiKey,
        signature: hash_hmac('sha256', buildSignatureString($method, $path, $query, $apiKey, $timestamp, false, '', $body), $secret),
        timestamp: $timestamp,
        nonce: '',
        method: $method,
        path: $path,
        query: $query,
        body: $body,
    );

    $user = new class($apiKey, $secret) implements UserInterface, PasswordAwareUserInterface {
        public function __construct(private string $identifier, private string $password)
        {
        }

        public function getIdentifier(): string
        {
            return $this->identifier;
        }

        public function getPassword(): string
        {
            return $this->password;
        }
    };

    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    $userProvider->shouldReceive('findByIdentifier')->once()->with($apiKey)->andReturn($user);

    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);

    $authenticator = createAuthenticator(
        userProvider: $userProvider,
        cache: $cache,
        nonceEnabled: false,
    );

    $passport = $authenticator->authenticate($request);

    expect($passport->getUserIdentifier())->toBe($apiKey)
        ->and($passport->getUser()->getIdentifier())->toBe($apiKey);
});

it('throws SignatureExpiredException when timestamp is invalid format', function (): void {
    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);

    $authenticator = createAuthenticator(
        userProvider: $userProvider,
        cache: $cache,
        nonceEnabled: false,
    );

    $request = createRequestMock(
        apiKey: 'client_a',
        signature: 'sig',
        timestamp: 'invalid_ts',
        nonce: '',
        method: 'GET',
        path: '/api/resource',
        query: [],
        body: '',
    );

    expect(fn () => $authenticator->authenticate($request))->toThrow(SignatureExpiredException::class);
});

it('throws UsedNonceException when nonce is reused', function (): void {
    $apiKey = 'client_a';
    $timestamp = (string) time();
    $nonce = 'nonce_1';
    $secret = 'top_secret';

    $request = createRequestMock(
        apiKey: $apiKey,
        signature: hash_hmac('sha256', buildSignatureString('GET', '/api/resource', [], $apiKey, $timestamp, true, $nonce, ''), $secret),
        timestamp: $timestamp,
        nonce: $nonce,
        method: 'GET',
        path: '/api/resource',
        query: [],
        body: '',
    );

    $user = new class($apiKey, $secret) implements UserInterface, PasswordAwareUserInterface {
        public function __construct(private string $identifier, private string $password)
        {
        }

        public function getIdentifier(): string
        {
            return $this->identifier;
        }

        public function getPassword(): string
        {
            return $this->password;
        }
    };

    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    $userProvider->shouldReceive('findByIdentifier')->never();

    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);
    $cache->shouldReceive('has')->once()->andReturn(true);

    $authenticator = createAuthenticator(
        userProvider: $userProvider,
        cache: $cache,
        nonceEnabled: true,
    );

    expect(fn () => $authenticator->authenticate($request))->toThrow(UsedNonceException::class);
});

it('throws InvalidSignatureException for mismatched signature', function (): void {
    $apiKey = 'client_a';
    $timestamp = (string) time();
    $secret = 'top_secret';

    $request = createRequestMock(
        apiKey: $apiKey,
        signature: 'wrong_signature',
        timestamp: $timestamp,
        nonce: '',
        method: 'GET',
        path: '/api/resource',
        query: [],
        body: '',
    );

    $user = new class($apiKey, $secret) implements UserInterface, PasswordAwareUserInterface {
        public function __construct(private string $identifier, private string $password)
        {
        }

        public function getIdentifier(): string
        {
            return $this->identifier;
        }

        public function getPassword(): string
        {
            return $this->password;
        }
    };

    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    $userProvider->shouldReceive('findByIdentifier')->once()->with($apiKey)->andReturn($user);

    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);

    $authenticator = createAuthenticator(
        userProvider: $userProvider,
        cache: $cache,
        nonceEnabled: false,
    );

    expect(fn () => $authenticator->authenticate($request))->toThrow(InvalidSignatureException::class);
});

it('throws RuntimeException when user is not PasswordAwareUserInterface', function (): void {
    $apiKey = 'client_a';
    $timestamp = (string) time();

    $request = createRequestMock(
        apiKey: $apiKey,
        signature: 'sig',
        timestamp: $timestamp,
        nonce: '',
        method: 'GET',
        path: '/api/resource',
        query: [],
        body: '',
    );

    $user = new class($apiKey) implements UserInterface {
        public function __construct(private string $identifier)
        {
        }

        public function getIdentifier(): string
        {
            return $this->identifier;
        }
    };

    /** @var MockInterface&UserProviderInterface $userProvider */
    $userProvider = Mockery::mock(UserProviderInterface::class);
    $userProvider->shouldReceive('findByIdentifier')->once()->with($apiKey)->andReturn($user);

    /** @var CacheInterface&MockInterface $cache */
    $cache = Mockery::mock(CacheInterface::class);

    $authenticator = createAuthenticator(
        userProvider: $userProvider,
        cache: $cache,
        nonceEnabled: false,
    );

    expect(fn () => $authenticator->authenticate($request))->toThrow(RuntimeException::class);
});

function createAuthenticator(UserProviderInterface $userProvider, CacheInterface $cache, bool $nonceEnabled): HmacAuthenticator
{
    return new HmacAuthenticator(
        apiKeyField: 'X-API-KEY',
        signatureField: 'X-SIGNATURE',
        timestampField: 'X-TIMESTAMP',
        nonceEnabled: $nonceEnabled,
        nonceField: 'X-NONCE',
        nonceCachePrefix: 'test:hmac:nonce',
        ttl: 60,
        leeway: 300,
        algo: 'sha256',
        userProvider: $userProvider,
        cache: $cache,
        encryptor: null,
        successHandler: null,
        failureHandler: null,
    );
}

function createRequestMock(
    string $apiKey,
    string $signature,
    string $timestamp,
    string $nonce,
    string $method,
    string $path,
    array $query,
    string $body,
): ServerRequestInterface {
    /** @var MockInterface&StreamInterface $bodyStream */
    $bodyStream = Mockery::mock(StreamInterface::class);
    $bodyStream->shouldReceive('getContents')->andReturn($body);
    $bodyStream->shouldReceive('isSeekable')->andReturn(true);
    $bodyStream->shouldReceive('rewind')->andReturnNull();

    /** @var MockInterface&UriInterface $uri */
    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('getPath')->andReturn($path);

    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    $request->shouldReceive('getHeaderLine')->with('X-API-KEY')->andReturn($apiKey);
    $request->shouldReceive('getHeaderLine')->with('X-SIGNATURE')->andReturn($signature);
    $request->shouldReceive('getHeaderLine')->with('X-TIMESTAMP')->andReturn($timestamp);
    $request->shouldReceive('getHeaderLine')->with('X-NONCE')->andReturn($nonce);
    $request->shouldReceive('getQueryParams')->andReturn($query);
    $request->shouldReceive('getBody')->andReturn($bodyStream);
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn($method);

    return $request;
}

function buildSignatureString(
    string $method,
    string $path,
    array $query,
    string $apiKey,
    string $timestamp,
    bool $nonceEnabled,
    string $nonce,
    string $body,
): string {
    ksort($query);
    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    $normalizedPath = $path === '' ? '/' : $path;

    $parts = [
        strtoupper($method),
        $normalizedPath,
        $queryString,
        $apiKey,
        $timestamp,
    ];

    if ($nonceEnabled) {
        $parts[] = $nonce;
    }

    $parts[] = hash('sha256', $body);

    return implode("\n", $parts);
}
