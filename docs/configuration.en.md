# Configuration Reference

> 中文文档请查看 [configuration.md](configuration.md)

The full configuration lives in `config/autoload/gaara.php`. The top-level structure has two sections: `guards` and `services`.

---

## Top-Level Structure

```php
return [
    'guards'   => [...],  // one or more Guard configurations
    'services' => [...],  // globally shared service configurations
];
```

---

## Guard Configuration

Each guard corresponds to a protected route scope. The key (e.g. `api`, `admin`) is the guard name.

```php
'guards' => [
    'api' => [
        'matcher'                 => [...],  // required: request matching rules
        'user_provider'           => [...],  // required: user provider
        'authenticators'          => [...],  // required: authenticator list
        'token_storage'           => [...],  // optional: token storage, default null
        'unauthenticated_handler' => [...],  // optional: unauthenticated handler
        'password_hasher'         => 'default', // optional: password hasher name
        'listeners'               => [...],  // optional: event listener list
        'authorization'           => [...],  // optional: authorization checker
    ],
],
```

---

## matcher (Request Matcher)

Controls which requests are handled by this guard.

```php
'matcher' => [
    'type'        => 'default',           // optional, default | custom, default: default
    'pattern'     => '^/api/',            // required when type==default, path match pattern (regex)
    'logout_path' => '/api/logout',       // optional, logout path
    'exclusions'  => ['^/api/health$'],   // optional, list of excluded paths
],
```

### Pattern Syntax

`pattern` uses regular expressions with `#` as the delimiter (so `/` in paths does not need escaping).

| Example | Description |
|---------|-------------|
| `'^/api/'` | All paths starting with `/api/` |
| `'^/api/users/\d+$'` | Exactly matches `/api/users/{number}` |
| `'/api'` | **Not recommended** — matches any path containing `/api` |
| `'GET ^/api/users$'` | Only matches GET requests to `/api/users` — format: **method + space + path** |
| `'POST\|PUT ^/api/users/\d+$'` | Matches POST or PUT method |

> **Note**: `logout_path` and `exclusions` support the same syntax. Plain strings (no regex metacharacters) use prefix matching (`str_starts_with`) for better performance.

### type == custom

Provide a class implementing `RequestMatcherInterface`:

```php
'matcher' => [
    'type'   => 'custom',
    'class'  => \App\Auth\MyRequestMatcher::class,
    'params' => [],
],
```

---

## user_provider (User Provider)

```php
'user_provider' => [
    'type' => 'model',  // memory | model | custom
],
```

### type == memory (In-memory users, suitable for testing)

```php
'user_provider' => [
    'type'  => 'memory',
    'users' => [
        'admin@example.com' => ['password' => '$2y$...'],  // bcrypt hashed password
    ],
],
```

### type == model (Database model)

```php
'user_provider' => [
    'type'       => 'model',
    'class'      => \App\Models\User::class,
    'identifier' => 'email',  // field used for lookup (also the user identifier)
],
```

The user model must implement `GaaraHyperf\User\UserInterface`. To enable password verification, also implement `PasswordAwareUserInterface`.

### type == custom

```php
'user_provider' => [
    'type'   => 'custom',
    'class'  => \App\Auth\MyUserProvider::class,
    'params' => [],
],
```

---

## authenticators (Authenticators)

Multiple authenticators can be configured simultaneously. Only the first authenticator whose `supports()` returns `true` for a given request will execute. See [Authenticators](authenticators.en.md) for details.

---

## token_storage (Token Storage)

```php
'token_storage' => [
    'type'   => 'null',   // session | null | custom; default: null
    'prefix' => 'api',    // required when type==session, prevents multiple guards sharing the same session key
],
```

- `null`: Stateless mode — re-authentication is required on every request (suitable for API token auth).
- `session`: Stateful mode — token is saved to the session after authentication (suitable for web form login).

---

## unauthenticated_handler (Unauthenticated Handler)

Triggered when a request fails authentication and no `failureHandler` is configured.

```php
'unauthenticated_handler' => [
    'type'             => 'default',      // default | redirect | custom
    'target_path'      => '/login',       // required when type==redirect
    'redirect_enabled' => true,           // whether to support ?redirect_to parameter
    'redirect_field'   => 'redirect_to',  // redirect parameter name
    'error_field'      => 'auth_error',   // session key for storing the error message
    'error_message'    => 'Please log in first',
    'class'            => MyHandler::class, // required when type==custom
],
```

- `default`: Throws `UnauthenticatedException` (typically converted to a 401 response by the framework's global exception handler in API scenarios).
- `redirect`: Redirects to the login page; optionally records the original URL.

---

## password_hasher (Password Hasher)

Specifies which password hasher service to use (corresponds to a key in `services.password_hashers`):

```php
'password_hasher' => 'default',
```

---

## listeners (Event Listeners)

```php
'listeners' => [
    [
        'class'  => \GaaraHyperf\EventListener\IPWhiteListListener::class,
        'params' => [
            'white_list' => ['192.168.1.0/24', '127.0.0.1'],
        ],
    ],
    [
        'class'  => \App\Auth\MyCustomListener::class,
        'params' => [],  // can be omitted when no params are needed
    ],
],
```

For built-in listeners, see [Event System](events.en.md).

---

## authorization (Authorization)

```php
'authorization' => [
    'rule_resolver' => [
        'class' => \GaaraHyperf\Authorization\HttpAuthorizationRuleResolver::class,
    ],
    'checker' => [
        'class' => \GaaraHyperf\Authorization\NullAuthorizationChecker::class,
    ],
    'access_denied_handler' => [
        'class' => \GaaraHyperf\Authorization\DefaultAccessDeniedHandler::class,
    ],
],
```

The default uses `HttpAuthorizationRuleResolver` to extract the object/path and action/method from the HTTP request, combined with `NullAuthorizationChecker` (all authenticated users pass). To use Casbin or another authorization framework, implement `AuthorizationCheckerInterface` or a custom `AuthorizationRuleResolverInterface`.

---

## services (Global Services)

### password_hashers (Password Hashers)

A built-in hasher named `default` (algorithm: `PASSWORD_BCRYPT`) is included.

```php
'services' => [
    'password_hashers' => [
        'default' => [
            'type' => 'default',          // default | custom
            'algo' => PASSWORD_ARGON2ID,  // optional when type==default, default: PASSWORD_BCRYPT
        ],
        'legacy' => [
            'type'  => 'custom',
            'class' => \App\Auth\LegacyPasswordHasher::class,
        ],
    ],
],
```

### csrf_token_managers (CSRF Token Managers)

A built-in session-based manager named `default` is included.

```php
'services' => [
    'csrf_token_managers' => [
        'default' => [
            'type'   => 'session',   // session | custom
            'prefix' => 'default',   // different prefix required for multiple managers
        ],
    ],
],
```

### opaque_token_managers (Opaque Token Managers)

```php
'services' => [
    'opaque_token_managers' => [
        'default' => [
            'type'                    => 'default',
            'prefix'                  => 'api',
            'idle_ttl'                => 1200,    // idle timeout in seconds, default 20 min
            'max_ttl'                 => 86400,   // maximum token lifetime in seconds, default 24 h
            'token_refresh'           => true,    // auto-renew on access
            'single_session'          => true,    // only one active token per user
            'ip_bind_enabled'         => false,   // bind to IP address
            'user_agent_bind_enabled' => false,   // bind to User-Agent
            'access_token_length'     => 64,      // generated token length (characters)
            'token_extractor' => [               // access token extractor config
                'type'   => 'header',            // header | cookie | body | custom
                'field'  => 'Authorization',
                'scheme' => 'Bearer',
            ],
            'token_responder' => [               // login success response config
                'type'         => 'body',        // body | cookie | custom
                'template'     => '{"code":0,"message":"success","data":{"access_token":"#ACCESS_TOKEN#","expires_in":#EXPIRES_IN#,"user_identifier":"#USER_IDENTIFIER#"}}',
                'cookie_name'      => 'access_token', // optional; used when type==cookie
                'cookie_path'      => '/',            // optional; used when type==cookie
                'cookie_domain'    => '',             // optional; used when type==cookie
                'cookie_http_only' => true,           // optional; used when type==cookie
                'cookie_same_site' => 'lax',          // optional; used when type==cookie
            ],
        ],
    ],
],
```
