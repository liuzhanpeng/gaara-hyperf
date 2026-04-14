# 认证器

认证器（Authenticator）是认证流程的核心。每个请求到达 Guard 时，Guard 会依次调用每个认证器的 `supports()` 方法，第一个返回 `true` 的认证器将负责完成认证。

---

## 表单登录认证器（form_login）

适用于传统 Web 应用，支持 HTML 表单提交、CSRF 保护、登录成功/失败重定向。

```php
'form_login' => [
    'check_path'        => '/admin/login',       // 必填：表单 POST 提交路径
    'target_path'       => '/admin/dashboard',   // 可选：登录成功后跳转路径
    'failure_path'      => '/admin/login',        // 可选：登录失败后跳转路径
    'redirect_enabled'  => true,                 // 可选：是否允许 ?redirect_to= 参数覆盖 target_path
    'redirect_field'    => 'redirect_to',         // 可选：重定向参数名
    'username_field'    => 'username',            // 可选：表单用户名字段名
    'password_field'    => 'password',            // 可选：表单密码字段名
    'error_message'     => '用户名或密码错误',      // 可选：失败提示消息
    'csrf_enabled'      => true,                  // 可选：启用 CSRF 保护
    'csrf_id'           => 'authenticate',         // 可选：CSRF Token ID
    'csrf_field'        => '_csrf_token',          // 可选：CSRF Token 表单字段名
    'csrf_token_manager' => 'default',             // 可选：使用哪个 CSRF 管理器
    'success_handler'   => null,                  // 可选：自定义成功处理器
    'failure_handler'   => null,                  // 可选：自定义失败处理器
],
```

**工作原理**：
1. 仅对 `check_path` 路径的 POST 请求触发（`supports()` 验证）
2. 从请求体中读取用户名和密码
3. 可选地验证 CSRF Token（防止跨站请求伪造）
4. 调用 `UserProvider` 查找用户，用 `PasswordHasher` 验证密码
5. 登录成功后迁移 Session 防止会话固定攻击

---

## JSON 登录认证器（json_login）

适用于前后端分离的 API 登录接口，接受 JSON 格式的请求体。

```php
'json_login' => [
    'check_path'      => '/api/login',                   // 必填：登录接口路径（POST）
    'username_field'  => 'username',                     // 可选：JSON 中的用户名字段
    'password_field'  => 'password',                     // 可选：JSON 中的密码字段
    'error_message'   => '用户名或密码错误',                // 可选：失败响应中的错误消息
    'success_handler' => [
        'class'  => \GaaraHyperf\Authenticator\OpaqueTokenResponseHandler::class,
        'params' => [
            'token_manager' => 'default',
        ],
    ],
    'failure_handler' => null,  // 不配置时默认返回 {"error": "..."} JSON 响应
],
```

`success_handler` 通常配置为 `OpaqueTokenResponseHandler`。响应格式由 `services.opaque_token_managers.<name>.token_responder` 控制。

---

## 不透明令牌认证器（opaque_token）

验证请求中携带的不透明令牌（如 Bearer Token），通常与 `json_login` 配合使用。

```php
'opaque_token' => [
    'token_manager'   => 'default',   // 可选：使用哪个令牌管理器（提取方式在 manager 中配置）
    'success_handler' => null,
    'failure_handler' => null,
],
```

**完整的无状态 API 认证示例**（`json_login` + `opaque_token`）：

```php
'authenticators' => [
    'json_login' => [
        'check_path' => '/api/login',
        'success_handler' => [
            'class'  => \GaaraHyperf\Authenticator\OpaqueTokenResponseHandler::class,
            'params' => ['token_manager' => 'default'],
        ],
    ],
    'opaque_token' => [],
],
'token_storage' => ['type' => 'null'],
```

---

## API Key 认证器（api_key）

从请求头中读取 API Key，适用于服务间调用。

```php
'api_key' => [
    'api_key_field' => 'X-API-KEY',  // 可选：请求头名称，默认 X-API-KEY
    'success_handler' => null,
    'failure_handler' => null,
],
```

UserProvider 的 `findByIdentifier()` 接收的标识符即为请求头中的 API Key 值。用户模型可在此处实现 API Key 的查询与验证。

---

## HMAC 签名认证器（hmac）

用于服务间调用，通过签名验证请求完整性，防止篡改和重放攻击。

```php
'hmac' => [
    'api_key_field'           => 'X-API-KEY',    // 请求头：API Key 字段名
    'signature_field'         => 'X-SIGNATURE',  // 请求头：签名字段名
    'timestamp_field'         => 'X-TIMESTAMP',  // 请求头：时间戳字段名（Unix 时间戳）
    'nonce_enabled'           => true,            // 是否启用 nonce（防重放）
    'nonce_field'             => 'X-NONCE',       // 请求头：nonce 字段名
    'nonce_cache_prefix'      => 'hmac_nonce',    // nonce 缓存前缀（需唯一）
    'ttl'                     => 60,              // 签名有效期（秒）
    'leeway'                  => 300,             // 时间戳容差（秒），默认 5 分钟
    'algo'                    => 'sha256',        // HMAC 算法
    'secret_encrypto_enabled' => false,           // 是否对 API Secret 加密存储
    'secret_encryptor' => [                       // secret_encrypto_enabled==true 时必填
        'type' => 'default',
        'algo' => 'AES-256-CBC',
        'key'  => env('HMAC_ENCRYPTION_KEY'),
    ],
],
```

**签名算法**（客户端须按相同规则生成签名）：

```
queryString = RFC3986 编码后按 key 排序拼接
bodyHash    = SHA256(requestBody)

signData = METHOD + "\n"
         + PATH + "\n"
         + queryString + "\n"
         + apiKey + "\n"
         + timestamp
         + (nonce_enabled ? "\n" + nonce : "") + "\n"
         + bodyHash

signature = HMAC(algo, signData, apiSecret)
```

---

## X.509 证书认证器（x509）

从 TLS 双向认证（mTLS）的客户端证书中提取用户标识。

```php
'x509' => [
    'ssl_client_s_dn_field' => 'SSL_CLIENT_S_DN',  // 存放证书 DN 的服务器参数名
    'identifier_field'      => 'cn',               // 用户标识来源：cn | email
],
```

需要 Web 服务器（Nginx/Apache）将客户端证书的 Subject DN 传递给 PHP（通过 `$_SERVER` 或请求属性）。

---

## 多认证器配置

同一个 Guard 下可以配置多个认证器，它们按配置顺序依次检查：

```php
'authenticators' => [
    // 顺序 1：优先检查 Bearer Token（已登录用户）
    'opaque_token' => [],

    // 顺序 2：登录接口
    'json_login' => [
        'check_path' => '/api/login',
        'success_handler' => [...],
    ],

    // 顺序 3：服务间调用走 API Key
    'api_key' => [
        'api_key_field' => 'X-SERVICE-KEY',
    ],
],
```

---

## 自定义认证器（custom）

```php
'authenticators' => [
    'custom' => [
        [
            'class'  => \App\Auth\WechatAuthenticator::class,
            'params' => ['app_id' => env('WECHAT_APP_ID')],
        ],
        [
            'class'  => \App\Auth\SmsCodeAuthenticator::class,
            'params' => [],
        ],
    ],
],
```

自定义认证器须实现 `GaaraHyperf\Authenticator\AuthenticatorInterface`，或继承 `AbstractAuthenticator`。详见 [扩展指南](extension.md)。
