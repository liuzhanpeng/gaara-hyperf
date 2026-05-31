# Quick Start (5 min)

> 中文文档请查看 [quickstart.md](quickstart.md)

**Goal**: Get a working authentication flow up and running without needing to understand the full architecture.

## Choose Your Scenario

- **Web form login (Session)**: suitable for admin panels and server-rendered apps.
- **Stateless API auth (JSON login + Opaque Token)**: suitable for SPA / mobile apps.

After publishing the config, `config/autoload/gaara.php` will contain two minimal example guards: `admin` (Web) and `api` (API).

## 1. Install and Publish Config

```bash
composer require lzpeng/gaara-hyperf
php bin/hyperf.php vendor:publish lzpeng/gaara-hyperf
```

## 2. Register Middleware

In `config/autoload/middlewares.php`:

```php
<?php

declare(strict_types=1);

return [
    'http' => [
        \GaaraHyperf\AuthMiddleware::class,
    ],
];
```

## 3. Implement the User Model

Adjust the namespace to match your project structure:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use GaaraHyperf\User\PasswordAwareUserInterface;
use GaaraHyperf\User\UserInterface;
use Hyperf\DbConnection\Model\Model;

class User extends Model implements UserInterface, PasswordAwareUserInterface
{
    public function getIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }
}
```

## 4. Create Minimal Routes

```php
<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;

Router::post('/admin/login', [\App\Controller\AuthController::class, 'login']);
Router::get('/admin/dashboard', [\App\Controller\DashboardController::class, 'index']);

Router::post('/api/login', [\App\Controller\ApiAuthController::class, 'login']);
Router::get('/api/me', [\App\Controller\MeController::class, 'show']);
```

## 5. Verify It Works

**Web scenario**:
- Visit `/admin/dashboard` — an unauthenticated request should trigger the unauthenticated handler.
- Submit `/admin/login` — on success you should be redirected to `/admin/dashboard`.

**API scenario**:
- `POST /api/login` → obtain an `access_token`.
- `GET /api/me` with `Authorization: Bearer <access_token>` → returns the current user.

## FAQ

- **Always unauthenticated**: Check that the route path matches the guard's `matcher.pattern`.
- **Login always fails**: Verify that `user_provider.identifier` matches the login field (e.g. `email`).
- **No token in API response**: Confirm that `json_login.success_handler` is set to `OpaqueTokenSuccessHandler`.

For more details, see:
- [Configuration Reference](configuration.en.md)
- [Authenticators](authenticators.en.md)
- [Scenario Guide](scenarios.en.md)
