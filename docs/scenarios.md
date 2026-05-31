# 场景化配置

本页用于快速选择适合的认证组合，减少在多个文档间来回查找。

## 场景 1：后台管理（表单登录 + Session）

适用：传统服务端渲染页面、管理后台。

推荐组合：
- `form_login`
- `token_storage.type=session`
- 可选 `listeners`：登录限频、IP 白名单

关键配置：

```php
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
            ],
        ],
        'token_storage' => [
            'type' => 'session',
            'prefix' => 'admin',
        ],
    ],
],
```

## 场景 2：前后端分离 API（JSON 登录 + Opaque Token）

适用：SPA、移动端、小程序。

推荐组合：
- `json_login`
- `opaque_token`
- `token_storage.type=null`

关键配置：

```php
'guards' => [
    'api' => [
        'matcher' => [
            'pattern' => '^/api/',
            'exclusions' => ['^/api/login$'],
        ],
        'user_provider' => [
            'type' => 'model',
            'class' => \App\Model\User::class,
            'identifier' => 'email',
        ],
        'authenticators' => [
            'json_login' => [
                'check_path' => '/api/login',
                'username_field' => 'email',
                'password_field' => 'password',
                'success_handler' => [
                    'class' => \GaaraHyperf\Authenticator\OpaqueTokenSuccessHandler::class,
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

## 场景 3：服务间调用（API Key）

适用：内部服务调用、低复杂度机器身份认证。

推荐组合：
- `api_key`
- 配合自定义 UserProvider 按 API key 查用户

## 场景 4：高安全服务间调用（HMAC）

适用：需要防重放、防篡改的服务间通信。

推荐组合：
- `hmac`
- 开启 `nonce_enabled`
- 根据时钟偏差设置 `ttl` 和 `leeway`

---

建议实践：
- 先从最小配置跑通，再逐步增加监听器和授权逻辑。
- 一个 guard 负责一类访问路径，避免 matcher 过度重叠。
- 生产环境优先使用 model/custom 用户提供器，避免 memory 用户。
