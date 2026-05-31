# 5 分钟快速开始

本指南目标：不需要理解完整架构，也能先跑通一个可用的认证流程。

## 场景选择

- Web 表单登录（Session）：适合后台管理系统。
- API 无状态认证（JSON 登录 + Opaque Token）：适合前后端分离。

发布配置后，`config/autoload/gaara.php` 已包含两个最小示例 guard：`admin`（Web）和 `api`（API）。

## 1. 安装并发布配置

```bash
composer require lzpeng/gaara-hyperf
php bin/hyperf.php vendor:publish lzpeng/gaara-hyperf
```

## 2. 注册中间件

在 `config/autoload/middlewares.php` 中注册：

```php
<?php

declare(strict_types=1);

return [
    'http' => [
        \GaaraHyperf\AuthMiddleware::class,
    ],
];
```

## 3. 实现用户模型

示例（按你的项目结构调整 namespace）：

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

## 4. 创建最小路由

```php
<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;

Router::post('/admin/login', [\App\Controller\AuthController::class, 'login']);
Router::get('/admin/dashboard', [\App\Controller\DashboardController::class, 'index']);

Router::post('/api/login', [\App\Controller\ApiAuthController::class, 'login']);
Router::get('/api/me', [\App\Controller\MeController::class, 'show']);
```

## 5. 验证是否跑通

Web 场景建议先验证：
- 访问 `/admin/dashboard`，未登录时应触发未认证流程。
- 提交 `/admin/login` 后，成功跳转到 `/admin/dashboard`。

API 场景建议验证：
- `POST /api/login` 获取 `access_token`。
- `GET /api/me` 携带 `Authorization: Bearer <access_token>` 成功返回当前用户。

## 常见问题

- 一直未认证：确认路由路径和 guard 的 `matcher.pattern` 是否匹配。
- 登录总失败：确认 `user_provider.identifier` 与登录字段一致（例如 `email`）。
- API 无 token：确认 `json_login.success_handler` 使用了 `OpaqueTokenSuccessHandler`。

更多配置细节见：
- [配置参考](configuration.md)
- [认证器说明](authenticators.md)
- [场景化配置](scenarios.md)
