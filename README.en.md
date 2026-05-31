# Gaara Hyperf Authentication

`gaara-hyperf` is an authentication component library for [Hyperf](https://hyperf.io/), inspired by Symfony Security. It provides a clean Guard / Authenticator / Event pipeline that covers a wide range of authentication scenarios.

> 中文文档请查看 [README.md](README.md)

## Features

- [x] Form Login (with optional CSRF protection)
- [x] JSON Login
- [x] Opaque Token authentication (IP binding, UA binding, single-session)
- [x] API Key authentication
- [x] HMAC Signature authentication
- [x] X.509 Client Certificate authentication
- [x] Built-in event listeners
    - [x] IP Whitelist listener
    - [x] Login attempt rate-limiting listener
    - [x] Password expiry policy listener
    - [x] Audit log listener

Additional authenticators are available as separate extensions:

- [x] JWT — [gaara-hyperf-jwt](https://github.com/liuzhanpeng/gaara-hyperf-jwt)
- [x] 2FA (TOTP / Email OTP / SMS OTP) — [gaara-hyperf-2fa](https://github.com/liuzhanpeng/gaara-hyperf-2fa)
- [x] WebAuthn (Passkey) — [gaara-hyperf-webauthn](https://github.com/liuzhanpeng/gaara-hyperf-webauthn)

---

## Installation

```bash
composer require lzpeng/gaara-hyperf
```

Publish the configuration file:

```bash
php bin/hyperf.php vendor:publish lzpeng/gaara-hyperf
```

The configuration will be published to `config/autoload/gaara.php`.

---

## Quick Start

### 1. Register the middleware

In `config/autoload/middlewares.php`:

```php
return [
    'http' => [
        \GaaraHyperf\AuthMiddleware::class,
    ],
];
```

Or attach it directly on a route group:

```php
use GaaraHyperf\AuthMiddleware;

Route::get('/profile', function () {
    // protected route
})->middleware([AuthMiddleware::class]);
```

### 2. Configure a Guard

A minimal Guard needs a **matcher**, a **user_provider**, and at least one **authenticator**:

```php
return [
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
                    'csrf_id'       => 'authenticate',
                    'csrf_field'    => '_csrf_token',
                ],
            ],
            'token_storage' => [
                'type'   => 'session',
                'prefix' => 'admin',
            ],
        ],
    ],
];
```

### 3. Implement the User model

```php
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\User\PasswordAwareUserInterface;

class User extends Model implements UserInterface, PasswordAwareUserInterface
{
    public function getIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
```

### 4. Access the authenticated user

```php
// Get the auth context via the helper
$context = auth();

// Current token
$token = $context->getToken();

// Current user object
$user = $context->getUser();
```

---

## Documentation

- [Quick Start (5 min)](docs/quickstart.en.md)
- [Configuration Reference](docs/configuration.en.md)
- [Scenario Guide](docs/scenarios.en.md) — choosing the right authenticator combination
- [Authenticators](docs/authenticators.en.md)
- [Extension Guide](docs/extension.en.md) — custom authenticators, user providers, listeners
- [Event System](docs/events.en.md)
- [Notes & Security Tips](docs/notes.en.md)
