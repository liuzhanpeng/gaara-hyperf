# Extension Guide

> 中文文档请查看 [extension.md](extension.md)

All core components in this library are interface-based. You can replace or extend any part by implementing the corresponding interface.

---

## Custom Authenticator

### Implementing AuthenticatorInterface

```php
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SmsCodeAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private \App\Service\SmsService $smsService,
        private \GaaraHyperf\UserProvider\UserProviderInterface $userProvider,
    ) {}

    /**
     * Determines whether this authenticator should handle the current request.
     * Keep this method lightweight — avoid expensive operations here.
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === '/api/sms-login'
            && $request->getMethod() === 'POST';
    }

    /**
     * Performs the authentication logic. Returns a Passport or throws AuthenticationException.
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $body  = (array) json_decode((string) $request->getBody(), true);
        $phone = $body['phone'] ?? '';
        $code  = $body['code']  ?? '';

        if (! $this->smsService->verifyCode($phone, $code)) {
            throw new \GaaraHyperf\Exception\InvalidCredentialsException('Invalid verification code');
        }

        $userIdentifier = $phone;

        return new Passport(
            userIdentifier: $userIdentifier,
            userLoader: fn() => $this->userProvider->findByIdentifier($userIdentifier),
        );
    }

    /**
     * Creates a Token from the Passport.
     * Usually just instantiate AuthenticatedToken.
     */
    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new \GaaraHyperf\Token\AuthenticatedToken(
            guardName: $guardName,
            userIdentifier: $passport->getUserIdentifier(),
        );
    }

    public function onAuthenticationSuccess(
        string $guardName,
        ServerRequestInterface $request,
        TokenInterface $token,
        Passport $passport,
    ): ?ResponseInterface {
        return null; // Returning null lets the framework continue processing the request
    }

    public function onAuthenticationFailure(
        string $guardName,
        ServerRequestInterface $request,
        \GaaraHyperf\Exception\AuthenticationException $exception,
        ?Passport $passport,
    ): ?ResponseInterface {
        return null; // Returning null delegates to UnauthenticatedHandler
    }

    public function isInteractive(): bool
    {
        return true; // Interactive authenticator (login endpoint)
    }
}
```

### Extending AbstractAuthenticator (Recommended)

`AbstractAuthenticator` already handles the delegation logic for `successHandler` / `failureHandler`. Extending it lets you focus on the core business logic:

```php
use GaaraHyperf\Authenticator\AbstractAuthenticator;
use GaaraHyperf\Authenticator\AuthenticationSuccessHandlerInterface;
use GaaraHyperf\Authenticator\AuthenticationFailureHandlerInterface;

class SmsCodeAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private \App\Service\SmsService $smsService,
        private \GaaraHyperf\UserProvider\UserProviderInterface $userProvider,
        ?AuthenticationSuccessHandlerInterface $successHandler = null,
        ?AuthenticationFailureHandlerInterface $failureHandler = null,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    public function supports(ServerRequestInterface $request): bool { ... }

    public function authenticate(ServerRequestInterface $request): Passport { ... }

    public function isInteractive(): bool { return true; }
}
```

### Register in Configuration

```php
'authenticators' => [
    'custom' => [
        [
            'class'  => \App\Auth\SmsCodeAuthenticator::class,
            'params' => [],  // additional constructor params (DI-injected dependencies do not need to be listed)
        ],
    ],
],
```

---

## Custom User Provider

Implement `UserProviderInterface`:

```php
use GaaraHyperf\UserProvider\UserProviderInterface;
use GaaraHyperf\User\UserInterface;

class RedisUserProvider implements UserProviderInterface
{
    public function __construct(
        private \Hyperf\Redis\Redis $redis,
    ) {}

    public function findByIdentifier(string $identifier): ?UserInterface
    {
        $data = $this->redis->hGetAll("user:{$identifier}");
        if (empty($data)) {
            return null;
        }
        return new \App\Auth\RedisUser($data);
    }
}
```

Configuration:

```php
'user_provider' => [
    'type'  => 'custom',
    'class' => \App\Auth\RedisUserProvider::class,
],
```

---

## Custom User Object

Implement the appropriate interfaces to enable the corresponding features:

```php
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\User\PasswordAwareUserInterface;
use GaaraHyperf\User\PasswordExpirationAwareUserInterface;

class AppUser implements UserInterface, PasswordAwareUserInterface, PasswordExpirationAwareUserInterface
{
    public function __construct(private array $data) {}

    // Required: unique user identifier (stored in the Token)
    public function getIdentifier(): string
    {
        return $this->data['email'];
    }

    // PasswordAwareUserInterface: enables password verification
    public function getPassword(): string
    {
        return $this->data['password'];
    }

    // PasswordExpirationAwareUserInterface: enables password expiration checks
    public function getExpiresAt(): \DateTimeInterface
    {
        return new \DateTimeImmutable($this->data['password_changed_at'] . ' +90 days');
    }
}
```

---

## Custom Authentication Success / Failure Handlers

### Success Handler

```php
use GaaraHyperf\Authenticator\AuthenticationSuccessHandlerInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\Passport\Passport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonTokenSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function handle(
        string $guardName,
        ServerRequestInterface $request,
        TokenInterface $token,
        Passport $passport,
    ): ?ResponseInterface {
        $response = new \Hyperf\HttpMessage\Server\Response();
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(json_encode([
                'user'  => $token->getUserIdentifier(),
                'guard' => $guardName,
            ])));
    }
}
```

### Failure Handler

```php
use GaaraHyperf\Authenticator\AuthenticationFailureHandlerInterface;
use GaaraHyperf\Exception\AuthenticationException;

class JsonFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function handle(
        string $guardName,
        ServerRequestInterface $request,
        AuthenticationException $exception,
        ?Passport $passport,
    ): ?ResponseInterface {
        // Return null to let UnauthenticatedHandler continue
        return null;
    }
}
```

Configure in the authenticator:

```php
'json_login' => [
    'check_path'      => '/api/login',
    'success_handler' => \App\Auth\JsonTokenSuccessHandler::class,
    'failure_handler' => \App\Auth\JsonFailureHandler::class,
],
```

---

## Custom Event Listeners

Listeners are implemented using Symfony's event-subscriber mechanism and fire at various stages of the authentication flow.

```php
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuditLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => ['onAuthenticationSuccess', Priority::LOW],
            AuthenticationFailureEvent::class => ['onAuthenticationFailure', Priority::LOW],
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $this->logger->info('Login successful', [
            'guard' => $event->getGuardName(),
            'user'  => $event->getToken()->getUserIdentifier(),
            'ip'    => $event->getRequest()->getServerParams()['remote_addr'] ?? '',
        ]);
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $this->logger->warning('Login failed', [
            'reason' => $event->getException()->getMessage(),
        ]);
    }
}
```

See [Event System](events.en.md) for full details.

---

## Custom IP Whitelist Provider

When the whitelist needs to be loaded dynamically from a database or Redis, implement `IPWhiteListProviderInterface`:

```php
use GaaraHyperf\IPWhiteListChecker\IPWhiteListProviderInterface;

class DbIpWhiteListProvider implements IPWhiteListProviderInterface
{
    public function __construct(
        private \App\Repository\IpRepository $repo,
    ) {}

    public function getWhiteList(): array
    {
        return $this->repo->getApprovedIps();
    }
}
```

Configuration:

```php
'listeners' => [
    [
        'class'  => \GaaraHyperf\EventListener\IPWhiteListListener::class,
        'params' => [
            'white_list' => \App\Auth\DbIpWhiteListProvider::class,
        ],
    ],
],
```

---

## Custom Authorization Checker

Implement `AuthorizationCheckerInterface` to integrate Casbin, Spatie Permission, or any other authorization framework:

```php
use GaaraHyperf\Authorization\AuthorizationCheckerInterface;
use GaaraHyperf\Token\TokenInterface;

class CasbinAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function __construct(
        private \Casbin\Enforcer $enforcer,
    ) {}

    /**
     * @param mixed $object The access object (e.g. route path, resource name)
     * @param mixed $action The access action (e.g. HTTP method, permission name); may be null
     */
    public function check(TokenInterface $token, mixed $object, mixed $action = null): bool
    {
        $user = $token->getUserIdentifier();
        $obj  = $object ?? '*';
        $act  = $action ?? '*';
        return $this->enforcer->enforce($user, $obj, $act);
    }
}
```

Configuration:

```php
'authorization' => [
    'checker' => [
        'class' => \App\Auth\CasbinAuthorizationChecker::class,
    ],
],
```

---

## Custom Password Hasher

```php
use GaaraHyperf\PasswordHasher\PasswordHasherInterface;

class LegacyMd5PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $password): string
    {
        return md5($password);
    }

    public function verify(string $password, string $hashedPassword): bool
    {
        return hash_equals(md5($password), $hashedPassword);
    }
}
```

Configuration:

```php
'services' => [
    'password_hashers' => [
        'legacy' => [
            'type'  => 'custom',
            'class' => \App\Auth\LegacyMd5PasswordHasher::class,
        ],
    ],
],
```

Specify it in the guard:

```php
'password_hasher' => 'legacy',
```

---

## Custom Opaque Token Issuer

If you need to replace the issuance, resolution, or revocation logic for Opaque Tokens (e.g. to use a database, remote session service, or custom storage), implement `OpaqueTokenIssuerInterface`.

The `OpaqueTokenManager` itself is still assembled by the framework and handles:
- Extracting the access token from the request
- Calling the custom issuer for issuance / resolution / revocation
- Generating the final response via the responder

```php
use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerInterface;
use GaaraHyperf\Token\TokenInterface;

class CustomOpaqueTokenIssuer implements OpaqueTokenIssuerInterface
{
    public function issue(TokenInterface $token): OpaqueToken
    {
        // Return an OpaqueToken object
    }

    public function resolve(string $accessToken): ?OpaqueToken
    {
        // Resolve the OpaqueToken from the access token string
    }

    public function revoke(string $accessToken): void
    {
        // Revoke the access token
    }
}
```

Configuration:

```php
'services' => [
    'opaque_token_managers' => [
        'custom' => [
            'type'  => 'custom',
            'class' => \App\Auth\CustomOpaqueTokenIssuer::class,
            'token_extractor' => [
                'type'   => 'header',
                'field'  => 'Authorization',
                'scheme' => 'Bearer',
            ],
            'token_responder' => [
                'type' => 'body',
            ],
        ],
    ],
],
```

---

## Custom Request Matcher

```php
use GaaraHyperf\RequestMatcher\RequestMatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

class TenantRequestMatcher implements RequestMatcherInterface
{
    public function matchesPattern(ServerRequestInterface $request): bool
    {
        return str_starts_with($request->getUri()->getPath(), '/tenant/');
    }

    public function matchesLogout(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === '/tenant/logout';
    }

    public function matchesExcluded(ServerRequestInterface $request): bool
    {
        return false;
    }
}
```

Configuration:

```php
'matcher' => [
    'type'  => 'custom',
    'class' => \App\Auth\TenantRequestMatcher::class,
],
```
