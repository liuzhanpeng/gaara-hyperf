# Gaara Hyperf Authentication 使用文档

## 概述

`gaara-hyperf` 是一个面向 [Hyperf](https://hyperf.io/) 的认证组件库，整体设计参考 Symfony Security，提供清晰的 Guard、Authenticator 与事件机制, 适用于各种认证场景。

### 特性

- [x] 表单登录认证
    - [x] CSRF 防护
- [x] JSON 登录认证
- [x] 不透明令牌认证
    - [x] IP 绑定 / UA 绑定
    - [x] 单会话
- [x] API Key 认证
- [x] HMAC 签名认证
- [x] X.509 客户端证书认证
- [x] 内置事件监听器
    - [x] IP 白名单监听器
    - [x] 登录尝试次数限制监听器
    - [x] 密码过期策略监听器
    - [x] 审计日志监听器

后续会以扩展库的形式提供更多认证方式：

- [x] JWT 认证  [(https://github.com/liuzhanpeng/gaara-hyperf-jwt)](https://github.com/liuzhanpeng/gaara-hyperf-jwt)
- [ ] 2FA 支持
- [ ] TOTP 认证
- [ ] WebAuthn 认证
- [ ] OAuth 2.0/OpenID Connect
- [ ] Step-up/Risk-based 认证

---

## 安装

```bash
composer require lzpeng/gaara-hyperf
```

发布配置文件：

```bash
php bin/hyperf.php vendor:publish lzpeng/gaara-hyperf
```

配置文件将发布到 `config/autoload/gaara.php`。

---

## 快速开始

### 1. 注册中间件

在 `config/autoload/middlewares.php` 中为需要保护的路由组注册中间件：

```php
return [
    'http' => [
        \GaaraHyperf\AuthMiddleware::class,
    ],
];
```

也可以在路由定义中直接使用中间件：

```php
use GaaraHyperf\AuthMiddleware;

Route::get('/profile', function () {
    // 受保护的路由
})->middleware([AuthMiddleware::class]);
``` 

### 2. 配置 Guard

推荐先阅读 [5 分钟快速开始](docs/quickstart.md)，再参考 [配置参考](docs/configuration.md) 做扩展。通常你至少需要为一个 Guard 指定：

- 请求匹配规则（`matcher`）
- 用户提供器（`user_provider`）
- 一个或多个认证器（`authenticators`）

示例：

```php
return [
    'guards' => [
        'admin' => [
            'matcher' => [
                'pattern' => '^/admin/',
                'logout_path' => '/admin/logout',
                'exclusions' => ['^/admin/login$'],
            ],
            'user_provider' => [
                'type' => 'model',
                'class' => \App\Model\User::class,
                'identifier' => 'email',
            ],
            'authenticators' => [
                'form_login' => [
                    'check_path' => '/admin/login',
                    'target_path' => '/admin/dashboard',
                    'failure_path' => '/admin/login',
                    'csrf_enabled' => true,
                    'csrf_id' => 'authenticate',
                    'csrf_field' => '_csrf_token',
                ],
            ],
            'token_storage' => [
                'type' => 'session',
                'prefix' => 'admin',
            ],
        ],
    ],
];
```

你可以按业务场景自由组合认证器、Token 存储、监听器和授权组件。更多场景组合见 [场景化配置](docs/scenarios.md)。

### 3. 实现用户模型

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

### 4. 获取当前用户

```php
// 通过辅助函数获取认证上下文
$context = auth();

// 获取当前 Token
$token = $context->getToken();

// 获取当前用户对象
$user = $context->getUser();
```

---

## 文档目录

- [5 分钟快速开始](docs/quickstart.md) — 复制配置并完成首个可运行认证流程
- [配置参考](docs/configuration.md) — 完整的配置项说明
- [场景化配置](docs/scenarios.md) — 按业务场景选择认证器组合
- [认证器](docs/authenticators.md) — 内置认证器的配置与使用
- [扩展指南](docs/extension.md) — 自定义认证器、用户提供者、监听器等
- [事件系统](docs/events.md) — 事件与监听器详解
- [注意事项](docs/notes.md) — 安全建议与常见问题
