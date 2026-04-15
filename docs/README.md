# Gaara Hyperf Authentication 使用文档

## 概述

`gaara-hyperf` 是一个基于 [Hyperf](https://hyperf.io/) 的认证库，设计思路参考 Symfony Security，强调可组合、可扩展与可观测。

组件支持有状态与无状态两类认证场景，适用于后台系统与 API 服务。

### 特性

- [x] 表单登录认证
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
    - [x] 登出撤销令牌监听器

后续会以扩展包的形式提供更多认证方式：

- [ ] JWT 认证
- [ ] 2FA支持
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

在 [docs/configuration.md](configuration.md) 中可以看到完整配置。通常你至少需要为一个 Guard 指定：

- 请求匹配规则（`matcher`）
- 用户提供器（`user_provider`）
- 一个或多个认证器（`authenticators`）

示例：

```php
return [
    'guards' => [
        'api' => [
            'matcher' => [
                'pattern' => '^/api/',
            ],
            'user_provider' => [
                // 用户加载方式
            ],
            'authenticators' => [
                // 例如 json_login、form_login、opaque_token、api_key 等
            ],
        ],
    ],
];
```

你可以按业务场景自由组合认证器、Token 存储、监听器和授权组件。

### 3. 实现用户模型

```php
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

- [配置参考](configuration.md) — 完整的配置项说明
- [认证器](authenticators.md) — 内置认证器的配置与使用
- [扩展指南](extension.md) — 自定义认证器、用户提供者、监听器等
- [事件系统](events.md) — 事件与监听器详解
- [注意事项](notes.md) — 安全建议与常见问题
