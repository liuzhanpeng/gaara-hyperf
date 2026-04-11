# 注意事项

---

## 安全建议

### 密码存储

- **不要**使用 MD5、SHA1 等普通哈希算法存储密码，始终使用 `PasswordHasher`（默认 `PASSWORD_BCRYPT`）
- 生产环境推荐使用 `PASSWORD_ARGON2ID`（需 PHP 7.3+ 且编译时包含 `--with-password-argon2`）：

```php
'services' => [
    'password_hashers' => [
        'default' => ['type' => 'default', 'algo' => PASSWORD_ARGON2ID],
    ],
],
```

### CSRF 保护

- `FormLoginAuthenticator` 的 CSRF 保护**默认启用**（`csrf_enabled: true`），不要轻易关闭
- 生成 CSRF Token 的推荐方式：

```php
$manager = $container->get(\GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface::class);
$token   = $manager->generate('authenticate'); // ID 须与配置的 csrf_id 一致
echo $token->getValue();
```

### HMAC 认证

- HMAC 密钥（API Secret）**不应明文存储于数据库**，启用 `secret_encrypto_enabled: true` 并使用强加密密钥
- `nonce_enabled` 应始终为 `true`，以防止重放攻击
- 建议 `ttl`（签名有效期）不超过 60 秒
- 签名验证时会读取整个请求体（`getContents()` 会消耗流），控制器中如需再次读取请求体，须确保框架已重置 Body 流

### 不透明令牌

- 生产环境建议启用 `single_session: true`，防止同一用户多处并发登录
- 高安全场景可同时启用 `ip_bind_enabled: true` 和 `user_agent_bind_enabled: true`（注意：IP 变化（如 4G 网络切换）会导致令牌失效）
- `max_ttl` 设置令牌绝对过期时间，即使频繁访问也不会无限续期，建议不超过 24 小时

### IP 白名单

- 在反向代理（Nginx、CDN）后部署时，`X-Forwarded-For` 可被客户端伪造。若依赖 IP 白名单，务必确保仅信任来自已知代理的 `X-Forwarded-For` 头
- CIDR 和通配符格式的 IP 匹配不区分 IPv4/IPv6，确保白名单正确涵盖两者

### 授权检查

- 默认的 `NullAuthorizationChecker` 允许所有已认证用户访问，仅适合基本场景
- 生产应用建议实现完整的授权检查器（如接入 Casbin）

---

## Hyperf 协程安全

本库针对 Swoole/Swow 协程环境设计，请注意：

- **Token 上下文**（`TokenContext`）使用 `Hyperf\Context` 存储，每个协程（请求）独立，**不会跨请求污染**
- **不要**将 `AuthContext` 或 Token 对象存储到类的成员变量中，否则在协程切换后数据会被其他请求覆盖：

```php
// ❌ 错误：会被其他协程覆盖
class MyService
{
    private ?TokenInterface $token;

    public function doSomething(): void
    {
        $this->token = AuthContext::getToken('api'); // 危险！
    }
}

// ✅ 正确：每次调用时从 Context 获取
class MyService
{
    public function doSomething(): void
    {
        $token = AuthContext::getToken('api'); // 安全
    }
}
```

- `OpaqueTokenManager` 使用 Redis 操作（ZADD/ZRANGE/HSET 等），这些操作在 Hyperf 中是协程安全的
- `SlidingWindowRateLimiter` 使用 Lua 脚本保证 Redis 操作的原子性

---

## 请求匹配器（Pattern）

### 正则 vs 纯字符串

`RequestMatcher` 会自动检测 pattern 是否包含正则元字符（`\.^$*+?()[]{}|`）：
- **包含**：使用 `preg_match` 进行正则匹配
- **不包含**：使用 `str_starts_with` 进行前缀匹配（性能更优）

因此：

```php
// 纯字符串 → 前缀匹配（匹配所有以 /api/ 开头的路径）
'pattern' => '/api/'

// 正则 → 精确匹配（仅匹配 /api/）
'pattern' => '^/api/$'
```

### logout_path 的破坏性变更

在当前版本中，`logout_path` 使用与 `pattern` 相同的匹配逻辑（支持正则）。

如果你之前配置了精确路径（如 `/admin/logout`），**现在需要加锚点**以防止子路径匹配：

```php
// 旧写法（可能误匹配 /admin/logout/confirm）
'logout_path' => '/admin/logout'

// 新写法（精确匹配）
'logout_path' => '^/admin/logout$'
```

---

## 多 Guard 场景

- 每个 Guard 有独立的 `matcher`，只处理匹配其 pattern 的请求
- 同一请求**只会被一个 Guard 处理**（所有 Guard 按配置顺序查找第一个匹配的）
- 使用 session 存储时，不同 Guard 必须配置不同的 `prefix`，否则会相互覆盖：

```php
'admin' => [
    'token_storage' => ['type' => 'session', 'prefix' => 'admin'],
],
'user' => [
    'token_storage' => ['type' => 'session', 'prefix' => 'user'],
],
```

---

## 认证器顺序

- 多个认证器按配置顺序检查，**第一个** `supports()` 返回 `true` 的认证器负责处理
- 登录认证器（`form_login`、`json_login`）通常放在**后面**，Token 验证认证器（`opaque_token`、`api_key`）放在**前面**，以避免每次请求都尝试解析登录表单

---

## Token 存储与无状态

- **无状态（null）**：每次请求都执行完整认证流程，适合 API。优点是无 Session 开销，缺点是无法主动登出
- **有状态（session）**：认证后保存 Token 到 Session，后续请求直接从 Session 恢复。适合 Web 应用，可主动登出（删除 Session）
- 混用时注意：`opaque_token` 认证器本身是无状态的，但搭配 `token_storage: session` 使用时，第一次认证后 Token 会被缓存到 Session，减少 Redis 访问

---

## 异常处理

认证层抛出的异常均继承自 `GaaraHyperf\Exception\AuthenticationException`。在 Hyperf 全局异常处理器中统一处理：

```php
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\AccessDeniedException;

class AuthExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        if ($throwable instanceof AccessDeniedException) {
            return $response->withStatus(403)
                ->withBody(new SwooleStream(json_encode(['error' => 'Forbidden'])));
        }

        if ($throwable instanceof AuthenticationException) {
            return $response->withStatus(401)
                ->withBody(new SwooleStream(json_encode(['error' => $throwable->getMessage()])));
        }

        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof AuthenticationException
            || $throwable instanceof AccessDeniedException;
    }
}
```

---

## 常见问题

**Q：配置了认证器但所有请求都跑到 `UnauthenticatedHandler`？**

检查 `matcher.pattern` 是否正确匹配当前请求路径。使用纯字符串时是**前缀匹配**，使用正则时注意锚点。

**Q：`opaque_token` 认证通过但下次请求又要重新登录？**

确认 `token_storage.type` 是否为 `null`（无状态模式下每次请求都需要携带令牌头，这是正常行为）。若希望复用，改为 `session` 模式。

**Q：FormLogin 登录成功后没有重定向？**

检查 `redirect_enabled` 是否为 `true`，且 `target_path` 已配置。也可能是 `successHandler` 返回了非重定向响应覆盖了默认行为。

**Q：HMAC 认证失败，报 `SignatureExpiredException`？**

检查客户端发送的 `X-TIMESTAMP` 是否为 Unix 时间戳（秒），且服务器与客户端时钟偏差不超过 `leeway` 配置值（默认 300 秒）。

**Q：限流器报错找不到 Redis？**

`RateLimiter` 使用 Hyperf 的 `CacheInterface`（默认对应 Redis）。确保 Hyperf 的 cache 组件已安装并配置：

```bash
composer require hyperf/cache
```
