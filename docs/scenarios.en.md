# Scenario Guide

> 中文文档请查看 [scenarios.md](scenarios.md)

Use this page to quickly select the right authentication combination for your use case, without having to search across multiple documents.

## Scenario 1: Admin Panel (Form Login + Session)

**Suitable for**: traditional server-rendered pages, admin back-ends.

**Recommended combination**:
- `form_login`
- `token_storage.type = session`
- Optional `listeners`: login rate limiting, IP whitelist

**Key configuration**:

```php
'guards' => [
    'admin' => [
        'matcher' => [
            'pattern'      => '^/admin/',
            'logout_path'  => '/admin/logout',
            'exclusions'   => ['^/admin/login$'],
        ],
        'user_provider' => [
            'type'       => 'model',
            'class'      => \App\Model\User::class,
            'identifier' => 'email',
        ],
        'authenticators' => [
            'form_login' => [
                'check_path'    => '/admin/login',
                'target_path'   => '/admin/dashboard',
                'failure_path'  => '/admin/login',
                'csrf_enabled'  => true,
            ],
        ],
        'token_storage' => [
            'type'   => 'session',
            'prefix' => 'admin',
        ],
    ],
],
```

## Scenario 2: SPA / Mobile API (JSON Login + Opaque Token)

**Suitable for**: single-page apps, mobile clients, mini-programs.

**Recommended combination**:
- `json_login`
- `opaque_token`
- `token_storage.type = null`

**Key configuration**:

```php
'guards' => [
    'api' => [
        'matcher' => [
            'pattern'    => '^/api/',
            'exclusions' => ['^/api/login$'],
        ],
        'user_provider' => [
            'type'       => 'model',
            'class'      => \App\Model\User::class,
            'identifier' => 'email',
        ],
        'authenticators' => [
            'json_login' => [
                'check_path'      => '/api/login',
                'username_field'  => 'email',
                'password_field'  => 'password',
                'success_handler' => [
                    'class'  => \GaaraHyperf\Authenticator\OpaqueTokenSuccessHandler::class,
                    'params' => ['token_manager' => 'default'],
                ],
            ],
            'opaque_token' => [
                'token_manager' => 'default',
            ],
        ],
    ],
],
```

## Scenario 3: Service-to-Service (API Key)

**Suitable for**: internal service calls, low-complexity machine identity authentication.

**Recommended combination**:
- `api_key`
- Custom `UserProvider` that looks up users by API key

## Scenario 4: High-Security Service Calls (HMAC)

**Suitable for**: service-to-service communication requiring replay protection and tamper detection.

**Recommended combination**:
- `hmac`
- Enable `nonce_enabled`
- Tune `ttl` and `leeway` to match clock skew tolerance

---

**Best practices**:
- Start with the minimal configuration and get it working before adding listeners and authorization logic.
- Assign each guard to one group of paths; avoid excessive overlap in matchers.
- In production, prefer `model` or `custom` user providers; avoid the `memory` provider.
