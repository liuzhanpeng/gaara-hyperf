# Notes & Security Tips

> 中文文档请查看 [notes.md](notes.md)

---

## Security Recommendations

### CSRF Protection

- CSRF protection in `FormLoginAuthenticator` is **enabled by default** (`csrf_enabled: true`). Do not disable it without a good reason.
- Recommended way to generate a CSRF token:

```php
$manager = $container->get(\GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface::class);
$token   = $manager->generate('authenticate'); // ID must match the configured csrf_id
echo $token->getValue();
```

### HMAC Authentication

- HMAC secrets (API secrets) **must not be stored in plaintext in the database**. Enable `secret_encrypto_enabled: true` and use a strong encryption key.
- `nonce_enabled` should always be `true` to prevent replay attacks.
- Keep `ttl` (signature validity period) at 60 seconds or less.
- Signature verification reads the entire request body (`getContents()` consumes the stream). If your controller also needs to read the body, make sure the framework has reset the body stream first.

### Opaque Tokens

- Enable `single_session: true` in production to prevent the same user from having multiple concurrent active sessions.
- For high-security scenarios, combine `ip_bind_enabled: true` and `user_agent_bind_enabled: true` (note: IP changes, e.g. on mobile networks, will invalidate the token).
- `max_ttl` sets the absolute expiry time for a token regardless of activity. Recommend keeping it at 24 hours or less.

### IP Whitelist

- When deployed behind a reverse proxy (Nginx, CDN), the `X-Forwarded-For` header can be spoofed by clients. If you rely on IP whitelisting, make sure you only trust `X-Forwarded-For` from known proxies.
- CIDR and wildcard IP matching is not IPv4/IPv6-aware — ensure the whitelist covers both formats as needed.

### Authorization

- The default `NullAuthorizationChecker` allows all authenticated users through. It is only appropriate for basic scenarios.
- Production applications should implement a full authorization checker (e.g. using Casbin).

---

## Hyperf Coroutine Safety

This library is designed for the Swoole/Swow coroutine environment. Keep the following in mind:

- **Token context** (`TokenContext`) uses `Hyperf\Context` for storage — each coroutine (request) has its own isolated context and **will not pollute other requests**.
- **Do not** store `AuthContext` or Token objects in class member variables. After a coroutine switch, another request may overwrite the value:

```php
// ❌ Wrong: will be overwritten by other coroutines
class MyService
{
    private ?TokenInterface $token;

    public function doSomething(): void
    {
        $this->token = AuthContext::getToken(); // Dangerous!
    }
}

// ✅ Correct: fetch from context on each call
class MyService
{
    public function doSomething(): void
    {
        $token = AuthContext::getToken(); // Safe
    }
}
```

- `OpaqueTokenManager` uses Redis operations (ZADD/ZRANGE/HSET etc.), which are coroutine-safe in Hyperf.
- `SlidingWindowRateLimiter` uses a Lua script to guarantee atomicity of Redis operations.

---

## Request Matcher (Pattern)

### Regex vs Plain String

`RequestMatcher` automatically detects whether a pattern contains regex metacharacters (`\.^$*+?()[]{}|`):
- **Contains metacharacters**: uses `preg_match` for regex matching.
- **No metacharacters**: uses `str_starts_with` for prefix matching (faster).

Therefore:

```php
// Plain string → prefix match (matches all paths starting with /api/)
'pattern' => '/api/'

// Regex → exact match (only matches /api/)
'pattern' => '^/api/$'
```

### Breaking Change: logout_path Anchoring

In the current version, `logout_path` uses the same matching logic as `pattern` (supports regex).

If you previously configured an exact path (e.g. `/admin/logout`), **you now need to add anchors** to avoid matching sub-paths:

```php
// Old (may accidentally match /admin/logout/confirm)
'logout_path' => '/admin/logout'

// New (exact match)
'logout_path' => '^/admin/logout$'
```

---

## Multiple Guards

- Each guard has its own independent `matcher` and only processes requests that match its pattern.
- **A single request is only handled by one guard** — guards are checked in config order; the first match wins.
- When using session storage, different guards **must** use different `prefix` values to avoid overwriting each other's session keys:

```php
'admin' => [
    'token_storage' => ['type' => 'session', 'prefix' => 'admin'],
],
'user' => [
    'token_storage' => ['type' => 'session', 'prefix' => 'user'],
],
```

---

## Authenticator Order

- Multiple authenticators are checked in config order. The **first** authenticator whose `supports()` returns `true` handles the request.
- Token-validating authenticators (`opaque_token`, `api_key`) should be listed **before** login authenticators (`form_login`, `json_login`) to avoid attempting to parse a login form on every request.

---

## Token Storage and Statelessness

- **Stateless (`null`)**: Full authentication is performed on every request. Suitable for API token auth. Pros: no session overhead. Cons: no active logout capability.
- **Stateful (`session`)**: After authentication, the token is saved to the session and restored on subsequent requests. Suitable for web apps; supports active logout (delete the session).
- Mixed use note: the `opaque_token` authenticator is stateless by itself, but when combined with `token_storage: session`, the token is cached in the session after the first authentication, reducing Redis calls.

---

## Exception Handling

All exceptions thrown by the authentication layer extend `GaaraHyperf\Exception\AuthenticationException`. Handle them centrally in a Hyperf global exception handler:

```php
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\AccessDeniedException;

class AuthExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        if ($throwable instanceof AccessDeniedException) {
            return $response->withStatus(403)
                ->withBody(new SwooleStream(json_encode(['error' => 'Forbidden'])));
        }

        if ($throwable instanceof AuthenticationException) {
            return $response->withStatus(401)
                ->withBody(new SwooleStream(json_encode(['error' => $throwable->getMessage()])));
        }

        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof AuthenticationException
            || $throwable instanceof AccessDeniedException;
    }
}
```

---

## FAQ

**Q: I configured an authenticator but all requests end up at `UnauthenticatedHandler`?**

Check that `matcher.pattern` correctly matches the current request path. Plain strings use **prefix matching**; when using regex, pay attention to anchors.

**Q: `opaque_token` authenticates successfully but the next request requires login again?**

Verify that `token_storage.type` is not `null` (stateless mode requires the token header on every request — this is expected). Switch to `session` mode if you want the token to be cached.

**Q: FormLogin succeeds but there is no redirect?**

Check that `redirect_enabled` is `true` and `target_path` is configured. It is also possible that a custom `successHandler` returned a non-redirect response and overrode the default behavior.

**Q: HMAC authentication fails with `SignatureExpiredException`?**

Check that the client sends `X-TIMESTAMP` as a Unix timestamp (seconds), and that the server–client clock difference is within the configured `leeway` (default: 300 seconds).

**Q: Rate limiter reports it cannot find Redis?**

`RateLimiter` uses Hyperf's `CacheInterface` (backed by Redis by default). Make sure the Hyperf cache component is installed and configured:

```bash
composer require hyperf/cache
```
