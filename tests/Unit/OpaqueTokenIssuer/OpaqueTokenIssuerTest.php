<?php

declare(strict_types=1);

use GaaraHyperf\IPResolver\IPResolverInterface;
use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuer;
use GaaraHyperf\Token\AuthenticatedToken;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;

afterEach(function (): void {
    Mockery::close();
});

it('throws when access token length is less than 32', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $request = createOpaqueTokenManagerTestRequest('UA');
    $ipResolver = createOpaqueTokenManagerTestIpResolver('127.0.0.1');

    expect(fn () => new OpaqueTokenIssuer(
        cache: $cache,
        request: $request,
        ipResolver: $ipResolver,
        prefix: 'gaara',
        idleTtl: 60,
        maxTtl: 300,
        tokenRefresh: false,
        singleSession: false,
        ipBindEnabled: false,
        userAgentBindEnabled: false,
        accessTokenLength: 16,
    ))->toThrow(InvalidArgumentException::class, 'at least 32');
});

it('throws when ttl is greater than max ttl', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $request = createOpaqueTokenManagerTestRequest('UA');
    $ipResolver = createOpaqueTokenManagerTestIpResolver('127.0.0.1');

    expect(fn () => new OpaqueTokenIssuer(
        cache: $cache,
        request: $request,
        ipResolver: $ipResolver,
        prefix: 'gaara',
        idleTtl: 301,
        maxTtl: 300,
        tokenRefresh: false,
        singleSession: false,
        ipBindEnabled: false,
        userAgentBindEnabled: false,
        accessTokenLength: 32,
    ))->toThrow(InvalidArgumentException::class, 'idle_ttl option must be less than or equal to max_ttl');
});

it('issues token and stores access token payload and user mapping for single session', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $request = createOpaqueTokenManagerTestRequest('Mozilla/5.0');
    $ipResolver = createOpaqueTokenManagerTestIpResolver('10.0.0.1');

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: $request,
        ipResolver: $ipResolver,
        tokenRefresh: false,
        singleSession: true,
        ipBindEnabled: true,
        userAgentBindEnabled: true,
    );

    $token = new AuthenticatedToken('api', 'user-1');
    $opaqueToken = $manager->issue($token);

    expect($opaqueToken)->toBeInstanceOf(OpaqueToken::class);

    $accessToken = $opaqueToken->accessToken();

    expect(strlen($accessToken))->toBe(32)
        ->and(ctype_xdigit($accessToken))->toBeTrue();

    $accessKey = 'gaara:' . $accessToken;
    $userKey = 'gaara:user:user-1';

    $payload = $cache->store[$accessKey];

    expect($cache->ttls[$accessKey])->toBe(60)
        ->and($cache->store[$userKey])->toBe($accessToken)
        ->and($cache->ttls[$userKey])->toBe(300)
        ->and($payload['token'])->toBe($token)
        ->and($payload['ip'])->toBe('10.0.0.1')
        ->and($payload['user_agent'])->toBe(hash('sha256', 'Mozilla/5.0'))
        ->and($payload['expires_in'])->toBe(300);
});

it('deletes previous access token when single session user already has token', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $cache->store['gaara:user:user-1'] = 'old-token';

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        tokenRefresh: false,
        singleSession: true,
        ipBindEnabled: false,
        userAgentBindEnabled: false,
    );

    $manager->issue(new AuthenticatedToken('api', 'user-1'));

    expect($cache->deletedKeys)->toContain('gaara:old-token');
});

it('does not create user mapping when single session is disabled', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        singleSession: false
    );

    $manager->issue(new AuthenticatedToken('api', 'user-1'));

    expect($cache->store)->not->toHaveKey('gaara:user:user-1');
});

it('returns null when access token does not exist', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1')
    );

    expect($manager->resolve('missing-token'))->toBeNull();
});

it('revokes and returns null when token is expired', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $token = new AuthenticatedToken('api', 'user-1');
    $cache->store['gaara:expired-token'] = [
        'token' => $token,
        'issued_at' => time() - 400,
        'expires_in' => 300,
    ];

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        singleSession: true
    );

    expect($manager->resolve('expired-token'))->toBeNull();
    expect($cache->deletedKeys)->toContain('gaara:expired-token');
    expect($cache->deletedKeys)->toContain('gaara:user:user-1');
});

it('returns null when ip binding does not match', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $token = new AuthenticatedToken('api', 'user-1');
    $cache->store['gaara:token-1'] = [
        'token' => $token,
        'issued_at' => time(),
        'expires_in' => 300,
        'ip' => '10.0.0.1',
    ];

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.2'),
        ipBindEnabled: true,
        userAgentBindEnabled: false
    );

    expect($manager->resolve('token-1'))->toBeNull();
});

it('returns null when ip binding is enabled but payload has no ip', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $token = new AuthenticatedToken('api', 'user-1');
    $cache->store['gaara:token-no-ip'] = [
        'token' => $token,
        'issued_at' => time(),
        'expires_in' => 300,
    ];

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        ipBindEnabled: true,
        userAgentBindEnabled: false
    );

    expect($manager->resolve('token-no-ip'))->toBeNull();
});

it('returns null when user agent binding does not match', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $token = new AuthenticatedToken('api', 'user-1');
    $cache->store['gaara:token-2'] = [
        'token' => $token,
        'issued_at' => time(),
        'expires_in' => 300,
        'user_agent' => md5('expected-ua'),
    ];

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('actual-ua'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        ipBindEnabled: false,
        userAgentBindEnabled: true
    );

    expect($manager->resolve('token-2'))->toBeNull();
});

it('returns null when user agent binding is enabled but payload has no user agent', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $token = new AuthenticatedToken('api', 'user-1');
    $cache->store['gaara:token-no-ua'] = [
        'token' => $token,
        'issued_at' => time(),
        'expires_in' => 300,
    ];

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('actual-ua'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        ipBindEnabled: false,
        userAgentBindEnabled: true
    );

    expect($manager->resolve('token-no-ua'))->toBeNull();
});

it('refreshes ttl on successful resolve when token refresh is enabled', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $token = new AuthenticatedToken('api', 'user-1');
    $cache->store['gaara:token-3'] = [
        'token' => $token,
        'issued_at' => time(),
        'expires_in' => 300,
    ];

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        tokenRefresh: true,
        ipBindEnabled: false,
        userAgentBindEnabled: false
    );

    $resolved = $manager->resolve('token-3');
    expect($resolved)->not->toBeNull()
        ->and($resolved->token())->toBe($token);
    expect($cache->ttls['gaara:token-3'])->toBe(60);
});

it('does not refresh ttl on successful resolve when token refresh is disabled', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $token = new AuthenticatedToken('api', 'user-1');
    $cache->store['gaara:token-no-refresh'] = [
        'token' => $token,
        'issued_at' => time(),
        'expires_in' => 300,
    ];

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        tokenRefresh: false,
        ipBindEnabled: false,
        userAgentBindEnabled: false
    );

    $resolved = $manager->resolve('token-no-refresh');
    expect($resolved)->not->toBeNull()
        ->and($resolved->token())->toBe($token);
    expect($cache->ttls)->not->toHaveKey('gaara:token-no-refresh');
});

it('revoke deletes both access token key and user mapping in single session mode', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $token = new AuthenticatedToken('api', 'user-1');
    $cache->store['gaara:token-4'] = [
        'token' => $token,
        'issued_at' => time(),
        'expires_in' => 300,
    ];
    $cache->store['gaara:user:user-1'] = 'token-4';

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        singleSession: true
    );

    $manager->revoke('token-4');

    expect($cache->deletedKeys)->toContain('gaara:token-4');
    expect($cache->deletedKeys)->toContain('gaara:user:user-1');
});

it('revoke only deletes access token key when single session is disabled', function (): void {
    $cache = new OpaqueTokenManagerTestInMemoryCache();
    $cache->store['gaara:token-5'] = [
        'token' => new AuthenticatedToken('api', 'user-1'),
        'issued_at' => time(),
        'expires_in' => 300,
    ];
    $cache->store['gaara:user:user-1'] = 'token-5';

    $manager = createOpaqueTokenManagerTestManager(
        cache: $cache,
        request: createOpaqueTokenManagerTestRequest('UA'),
        ipResolver: createOpaqueTokenManagerTestIpResolver('10.0.0.1'),
        singleSession: false
    );

    $manager->revoke('token-5');

    expect($cache->deletedKeys)->toContain('gaara:token-5');
    expect($cache->deletedKeys)->not->toContain('gaara:user:user-1');
    expect($cache->store['gaara:user:user-1'])->toBe('token-5');
});

function createOpaqueTokenManagerTestManager(
    CacheInterface $cache,
    RequestInterface $request,
    IPResolverInterface $ipResolver,
    bool $tokenRefresh = false,
    bool $singleSession = false,
    bool $ipBindEnabled = false,
    bool $userAgentBindEnabled = false,
): OpaqueTokenIssuer {
    return new OpaqueTokenIssuer(
        cache: $cache,
        request: $request,
        ipResolver: $ipResolver,
        prefix: 'gaara',
        idleTtl: 60,
        maxTtl: 300,
        tokenRefresh: $tokenRefresh,
        singleSession: $singleSession,
        ipBindEnabled: $ipBindEnabled,
        userAgentBindEnabled: $userAgentBindEnabled,
        accessTokenLength: 32,
    );
}

function createOpaqueTokenManagerTestRequest(string $userAgent): RequestInterface
{
    /** @var MockInterface&RequestInterface $request */
    $request = Mockery::mock(RequestInterface::class);
    $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn($userAgent);

    return $request;
}

function createOpaqueTokenManagerTestIpResolver(string $ip): IPResolverInterface
{
    /** @var IPResolverInterface&MockInterface $ipResolver */
    $ipResolver = Mockery::mock(IPResolverInterface::class);
    $ipResolver->shouldReceive('resolve')->andReturn($ip);

    return $ipResolver;
}

class OpaqueTokenManagerTestInMemoryCache implements CacheInterface
{
    public array $store = [];

    public array $ttls = [];

    public array $deletedKeys = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->store[$key] = $value;
        $this->ttls[$key] = $ttl;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key], $this->ttls[$key]);
        $this->deletedKeys[] = $key;

        return true;
    }

    public function clear(): bool
    {
        $this->store = [];
        $this->ttls = [];
        $this->deletedKeys = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }
}
