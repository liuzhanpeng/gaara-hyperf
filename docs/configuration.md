# 配置参考

完整配置结构位于 `config/autoload/gaara.php`，顶层分为 `guards` 和 `services` 两个部分。

---

## 顶层结构

```php
return [
    'guards'   => [...],  // 一个或多个 Guard 配置
    'services' => [...],  // 全局共享服务配置
];
```

---

## Guard 配置

每个 Guard 对应一个受保护的路由范围。键名（如 `api`、`admin`）即为 Guard 名称。

```php
'guards' => [
    'api' => [
        'matcher'               => [...],  // 必填：请求匹配规则
        'user_provider'         => [...],  // 必填：用户提供者
        'authenticators'        => [...],  // 必填：认证器列表
        'token_storage'         => [...],  // 可选：Token 存储器，默认 null
        'unauthenticated_handler' => [...],// 可选：未认证处理器
        'password_hasher'       => 'default', // 可选：密码哈希器名称
        'listeners'             => [...],  // 可选：事件监听器列表
        'authorization'         => [...],  // 可选：授权检查器
    ],
],
```

---

## matcher（请求匹配器）

控制哪些请求由该 Guard 处理。

```php
'matcher' => [
    'type'       => 'default',     // 可选，目前支持 default / custom，默认 default
    'pattern'    => '^/api/',      // type==default 必填，路径匹配模式（正则）
    'logout_path' => '/api/logout',// 可选，登出路径
    'exclusions' => ['^/api/health$'], // 可选，排除路径列表
],
```

### pattern 语法

`pattern` 使用正则表达式，以 `#` 为定界符（因此路径中的 `/` 无需转义）。

| 示例 | 说明 |
|------|------|
| `'^/api/'` | 所有 `/api/` 开头的路径 |
| `'^/api/users/\d+$'` | 精确匹配 `/api/users/{数字}` |
| `'/api'` | **不推荐**，会匹配任意包含 `/api` 的路径 |
| `'GET ^/api/users$'` | 仅匹配 GET 请求的 `/api/users`，**方法+空格+路径** 格式 |
| `'POST\|PUT ^/api/users/\d+$'` | 匹配 POST 或 PUT 方法 |

> **注意**：使用 `logout_path` 和 `exclusions` 时，同样支持上述语法。若是纯字符串（不含正则元字符），会使用前缀匹配（`str_starts_with`），性能更优。

### type == custom

需提供实现了 `RequestMatcherInterface` 的类：

```php
'matcher' => [
    'type'  => 'custom',
    'class' => \App\Auth\MyRequestMatcher::class,
    'params' => [],
],
```

---

## user_provider（用户提供者）

```php
'user_provider' => [
    'type' => 'model',  // memory | model | custom
],
```

### type == memory（内存用户，适合测试）

```php
'user_provider' => [
    'type'  => 'memory',
    'users' => [
        'admin@example.com' => ['password' => '$2y$...'],  // bcrypt 哈希密码
    ],
],
```

### type == model（数据库模型）

```php
'user_provider' => [
    'type'       => 'model',
    'class'      => \App\Models\User::class,
    'identifier' => 'email',  // 用于查询的字段名（同时也是用户标识符）
],
```

用户模型须实现 `GaaraHyperf\User\UserInterface`；若需密码验证，还需实现 `PasswordAwareUserInterface`。

### type == custom

```php
'user_provider' => [
    'type'   => 'custom',
    'class'  => \App\Auth\MyUserProvider::class,
    'params' => [],
],
```

---

## authenticators（认证器）

支持同时配置多个认证器，每个请求只有第一个 `supports()` 返回 `true` 的认证器会执行。详见 [认证器文档](authenticators.md)。

---

## token_storage（Token 存储器）

```php
'token_storage' => [
    'type'   => 'null',     // session | null | custom；默认 null
    'prefix' => 'api',      // type==session 时必填，防止多 Guard 共用同一 Session Key
],
```

- `null`：无状态模式，每次请求都需要重新认证（适合 API Token 认证）
- `session`：有状态模式，认证后 Token 保存在 Session 中（适合 Web 表单登录）

---

## unauthenticated_handler（未认证处理器）

请求未通过认证且无 failureHandler 处理时触发。

```php
'unauthenticated_handler' => [
    'type'           => 'default',       // default | redirect | custom
    'target_path'    => '/login',        // type==redirect 必填
    'redirect_enabled' => true,          // 是否允许 ?redirect_to 参数
    'redirect_field' => 'redirect_to',   // 重定向参数名
    'error_field'    => 'auth_error',    // Session 中存储错误消息的键名
    'error_message'  => '请先登录',       // 错误消息
    'class'          => MyHandler::class,// type==custom 必填
],
```

- `default`：抛出 `UnauthenticatedException`（API 场景下通常由框架全局异常处理器转换为 401）
- `redirect`：重定向到登录页，支持记录原始 URL

---

## password_hasher（密码哈希器）

指定使用哪个密码哈希器服务（对应 `services.password_hashers` 中的键名）：

```php
'password_hasher' => 'default',
```

---

## listeners（事件监听器）

```php
'listeners' => [
    [
        'class'  => \GaaraHyperf\EventListener\IPWhiteListListener::class,
        'params' => [
            'white_list' => ['192.168.1.0/24', '127.0.0.1'],
        ],
    ],
    \App\Auth\MyCustomListener::class,  // 无参数时可直接写类名
],
```

内置监听器详见 [事件系统](events.md)。

---

## authorization（授权检查器）

```php
'authorization' => [
    'checker' => [
        'class' => \GaaraHyperf\Authorization\NullAuthorizationChecker::class,
    ],
    'access_denied_handler' => [
        'class' => \GaaraHyperf\Authorization\DefaultAccessDeniedHandler::class,
    ],
],
```

默认使用 `NullAuthorizationChecker`（任何已认证用户均可通过）。如需使用 Casbin 等授权框架，实现 `AuthorizationCheckerInterface` 并配置即可。

---

## services（全局服务）

### password_hashers（密码哈希器）

内置一个名为 `default` 的哈希器（算法：`PASSWORD_BCRYPT`）。

```php
'services' => [
    'password_hashers' => [
        'default' => [
            'type' => 'default',          // default | custom
            'algo' => PASSWORD_ARGON2ID,  // type==default 可选，默认 PASSWORD_BCRYPT
        ],
        'legacy' => [
            'type'  => 'custom',
            'class' => \App\Auth\LegacyPasswordHasher::class,
        ],
    ],
],
```

### csrf_token_managers（CSRF 令牌管理器）

内置一个名为 `default` 的 Session-based 管理器。

```php
'services' => [
    'csrf_token_managers' => [
        'default' => [
            'type'   => 'session',    // session | custom
            'prefix' => 'default',    // 多个管理器时须配置不同前缀
        ],
    ],
],
```

### opaque_token_managers（不透明令牌管理器）

```php
'services' => [
    'opaque_token_managers' => [
        'default' => [
            'type'                    => 'default',
            'prefix'                  => 'api',
            'ttl'                     => 1200,     // Token 有效期（秒），默认 20 分钟
            'max_ttl'                 => 86400,    // Token 最大生命周期（秒），默认 24 小时
            'token_refresh'           => true,     // 访问时自动续期
            'single_session'          => true,     // 同一用户只允许一个有效 Token
            'ip_bind_enabled'         => false,    // 绑定 IP
            'user_agent_bind_enabled' => false,    // 绑定 User-Agent
            'access_token_length'     => 64,       // 生成令牌的长度（字符数）
        ],
    ],
],
```
