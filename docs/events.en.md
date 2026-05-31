# Event System

> 中文文档请查看 [events.md](events.md)

The authentication flow uses Symfony's EventDispatcher component internally. Each guard creates its own independent event dispatcher at initialization time and registers both built-in listeners and custom listeners as subscribers.

The public-facing dependency is the `EventDispatcherInterface` abstraction, but the actual dispatcher used at runtime is `symfony/event-dispatcher`.

---

## Event Reference

### CheckPassportEvent

**When fired**: after the authenticator calls `authenticate()` to create a Passport, and before a Token is created.

**Purpose**: validate Badges in the Passport (e.g. password, CSRF token); can abort the authentication flow at this point.

```php
use GaaraHyperf\Event\CheckPassportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyPassportListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => 'onCheckPassport',
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $request  = $event->getRequest();

        if ($this->isBlacklisted($request)) {
            throw new \GaaraHyperf\Exception\AuthenticationException('IP is banned');
        }

        /** @var \App\Auth\EmailVerifiedBadge|null $badge */
        $badge = $passport->getBadge(\App\Auth\EmailVerifiedBadge::class);
        if ($badge && ! $badge->isResolved()) {
            $badge->markResolved();
        }
    }
}
```

**Available methods**:
- `getGuardName(): string` — current guard name
- `getPassport(): Passport` — current Passport
- `getRequest(): ServerRequestInterface` — current request
- `getAuthenticator(): AuthenticatorInterface` — the authenticator that triggered authentication

---

### AuthenticationSuccessEvent

**When fired**: after authentication succeeds and a Token has been created (before it is saved to TokenStorage).

**Purpose**: write audit logs, send notifications, replace the Token (e.g. upgrade to a two-factor auth token).

```php
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginAuditListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $this->logger->info('User logged in', [
            'guard'         => $event->getGuardName(),
            'user'          => $event->getToken()->getUserIdentifier(),
            'authenticator' => $event->getAuthenticator()::class,
        ]);
    }
}
```

**Available methods**:
- `getGuardName(): string`
- `getToken(): TokenInterface` — the current Token
- `setToken(TokenInterface): void` — **replace the Token** (e.g. upgrade a plain Token to a TwoFactorToken)
- `getPassport(): Passport`
- `getRequest(): ServerRequestInterface`
- `getResponse(): ?ResponseInterface` — the response returned by the authenticator (may be null)
- `setResponse(?ResponseInterface): void` — modify the response
- `getPreviousToken(): ?TokenInterface` — the old token from TokenStorage before this authentication
- `getAuthenticator(): AuthenticatorInterface`

---

### AuthenticationFailureEvent

**When fired**: after authentication fails (after an `AuthenticationException` is thrown).

**Purpose**: write failure logs, send alerts, update failure counters.

```php
use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Exception\InvalidCredentialsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginFailureListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
        ];
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof InvalidCredentialsException) {
            $this->alertService->notify('Too many incorrect password attempts');
        }
    }
}
```

**Available methods**:
- `getGuardName(): string`
- `getException(): AuthenticationException`
- `getPassport(): ?Passport` — the Passport created before the failure (may be null)
- `getRequest(): ServerRequestInterface`
- `getResponse(): ?ResponseInterface`
- `setResponse(?ResponseInterface): void`
- `getAuthenticator(): AuthenticatorInterface`

---

### LogoutEvent

**When fired**: when the request path matches `logout_path` and a Token exists.

**Purpose**: revoke tokens, clear the session, write logout logs.

```php
use GaaraHyperf\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LogoutListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();

        $this->logger->info('User logged out', [
            'user' => $token->getUserIdentifier(),
        ]);

        $event->setResponse($this->createLogoutResponse());
    }
}
```

**Available methods**:
- `getToken(): TokenInterface`
- `getRequest(): ServerRequestInterface`
- `getResponse(): ?ResponseInterface`
- `setResponse(ResponseInterface): void`

---

## Built-in Listeners

Built-in listeners are registered via the guard's `listeners` config key and only apply to that guard.

### IPWhiteListListener (IP Whitelist)

Validates the client IP against a whitelist during `CheckPassportEvent`.

```php
[
    'class'  => \GaaraHyperf\EventListener\IPWhiteListListener::class,
    'params' => [
        'white_list' => [
            '127.0.0.1',
            '192.168.1.0/24',   // CIDR
            '10.0.*.*',         // Wildcard
        ],
        // Or use a dynamic provider:
        // 'white_list' => \App\Auth\DbIpWhiteListProvider::class,
    ],
],
```

IP whitelist supports three formats:
- Exact IP: `192.168.1.100`
- CIDR subnet: `172.16.0.0/12`
- Wildcard: `10.*.*.*`

IP resolution priority: `CF-Connecting-IP` > `X-Real-IP` > `X-Forwarded-For` > `remote_addr`.

---

### LoginAttemptLimitListener (Login Rate Limiter)

Rate-limits login requests during `CheckPassportEvent` to prevent brute-force attacks. The rate-limit key is `IP + user identifier`.

```php
[
    'class'  => \GaaraHyperf\EventListener\LoginAttemptLimitListener::class,
    'params' => [
        'options' => [
            'prefix'   => 'login_limit',
            'limit'    => 5,
            'interval' => 300,  // time window in seconds
        ],
    ],
],
```

The rate limiter always uses the `sliding_window` algorithm (smooth, no window-boundary spike). No need to configure a `type`.

On authentication failure (`AuthenticationFailureEvent`) the counter is incremented automatically; on success (`AuthenticationSuccessEvent`) the counter is reset automatically.

---

### PasswordExpirationListener (Password Expiration)

Checks whether a user's password has expired after a successful authentication (`AuthenticationSuccessEvent`).

The user model must implement `PasswordExpirationAwareUserInterface` (returning `getExpiresAt(): DateTimeInterface`).

```php
[
    'class'  => \GaaraHyperf\EventListener\PasswordExpirationListener::class,
    'params' => [
        'excluded_paths' => ['^/api/password/change$'],  // paths to skip
        'warning_days'   => 7,                           // days before expiry to issue a warning (throws PasswordExpiredException)
    ],
],
```

---

### OpaqueTokenRevokeLogoutListener (Revoke Token on Logout)

Revokes the current Opaque Token when `LogoutEvent` fires, preventing token reuse.

This listener is **automatically registered by the framework** when the `opaque_token` authenticator is used — you normally **do not need to add it manually** to `listeners`. It only performs revocation for `POST` logout requests.

To use a non-default manager, configure it directly in the authenticator:

```php
'authenticators' => [
    'opaque_token' => [
        'token_manager' => 'default',
    ],
],
```

---

### AuditLogListener (Audit Log)

Records login success, login failure, and logout events, including IP, User-Agent, and timestamp.

```php
[
    'class' => \GaaraHyperf\EventListener\AuditLogListener::class,
],
```

Logs are emitted via Hyperf's Logger system, using the `default` log channel by default.

---

### PasswordBadgeCheckListener (Password Badge Validator)

A built-in listener **automatically registered by the library** — no manual configuration needed.

Validates the `PasswordBadge` during `CheckPassportEvent` (calls `PasswordHasher` to compare the password hash). `FormLoginAuthenticator` and `JsonLoginAuthenticator` automatically add a `PasswordBadge` to the Passport.

---

### CsrfTokenBadgeCheckListener (CSRF Badge Validator)

A built-in listener **automatically registered by the library** — no manual configuration needed.

Validates the `CsrfTokenBadge` during `CheckPassportEvent`. `FormLoginAuthenticator` automatically adds a `CsrfTokenBadge` when CSRF protection is enabled.

---

## Registering Custom Listeners

Configure them in the guard's `listeners` array:

```php
'listeners' => [
    \App\Auth\MyListener::class,
],
```

Custom listeners must implement Symfony's `EventSubscriberInterface` and declare the events they handle in `getSubscribedEvents()`. The framework calls `addSubscriber()` on the guard's event dispatcher at initialization time.

Example:

```php
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => 'onSuccess',
        ];
    }

    public function onSuccess(AuthenticationSuccessEvent $event): void
    {
        // Custom logic here
    }
}
```

> **Note**: Listeners fire in registration order. Throwing an exception inside a `CheckPassportEvent` listener immediately aborts the authentication flow and triggers `AuthenticationFailureEvent`.
