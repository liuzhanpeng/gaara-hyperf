# Gaara Hyperf Authentication 使用文档

## 概述

`gaara-hyperf` 是一个基于 [Hyperf](https://hyperf.io/) 框架的身份认证库，设计灵感来源于 Symfony Security。它提供了完整的认证、授权、限流、会话管理等功能，支持有状态（Session）和无状态（Token）两种认证模式。

### 核心概念

| 概念 | 说明 |
|------|------|
| **Guard** | 认证守卫，对应一个受保护的路由范围（如 `/admin/`），每个 Guard 独立配置 |
| **Authenticator** | 认证器，负责从请求中提取凭证并验证用户身份 |
| **Passport** | 认证上下文，在认证流程中传递用户信息和认证标识（Badge） |
| **Token** | 认证令牌，表示已通过认证的用户状态，存储到 TokenStorage |
| **UserProvider** | 用户提供者，根据标识符加载用户对象 |
| **EventListener** | 事件监听器，在认证各阶段执行附加逻辑（限流、IP白名单等） |

### 认证流程

```
请求
  │
  ▼
AuthMiddleware（中间件）
  │
  ▼
Guard.authenticate()
  ├─ 从 TokenStorage 加载已有 Token → 直接通过
  │
  └─ 遍历 Authenticator 列表
       ├─ supports() == false → 跳过
       └─ supports() == true
            ├─ authenticate() → 创建 Passport
            ├─ 触发 CheckPassportEvent（Badge 校验）
            ├─ createToken() → 创建 AuthenticatedToken
            ├─ 触发 AuthenticationSuccessEvent
            ├─ 保存 Token 到 TokenStorage
            └─ 调用 successHandler

失败时触发 AuthenticationFailureEvent → failureHandler → UnauthenticatedHandler
```

---

## 安装

```bash
composer require gaara-hyperf/auth
```

发布配置文件：

```bash
php bin/hyperf.php vendor:publish gaara-hyperf/auth
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

### 2. 配置 Guard

在 `config/autoload/gaara.php` 中配置至少一个 Guard：

```php
return [
    'guards' => [
        'api' => [
            'matcher' => [
                'pattern' => '^/api/', // 匹配所有 /api/ 开头的路由
            ],
            'user_provider' => [
                'type' => 'model',
                'class' => \App\Models\User::class,
                'identifier' => 'email',
            ],
            'authenticators' => [
                'json_login' => [
                    'check_path' => '/api/login',
                    'success_handler' => [
                        'class' => \GaaraHyperf\Authenticator\OpaqueTokenResponseHandler::class,
                        'params' => [
                            'token_manager' => 'default',
                            'response_template' => '{"access_token":"#ACCESS_TOKEN#"}',
                        ],
                    ],
                ],
                'opaque_token' => [],
            ],
        ],
    ],
];
```

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
