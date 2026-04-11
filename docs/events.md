# 事件系统

认证流程的各个阶段会通过 PSR-14 `EventDispatcherInterface` 分发事件。监听器可以在这些阶段注入额外逻辑（如日志、限流、校验），或修改认证结果。

---

## 事件列表

### CheckPassportEvent

**触发时机**：认证器调用 `authenticate()` 创建 Passport 之后、创建 Token 之前。

**用途**：校验 Passport 中的 Badge（如密码、CSRF Token），可在此处阻断认证流程。

```php
use GaaraHyperf\Event\CheckPassportEvent;

class MyPassportListener
{
    public function handle(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $request  = $event->getRequest();

        // 检查 IP 是否在黑名单
        if ($this->isBlacklisted($request)) {
            throw new \GaaraHyperf\Exception\AuthenticationException('IP 已被封禁');
        }

        // 标记自定义 Badge 为已验证
        /** @var \App\Auth\EmailVerifiedBadge $badge */
        $badge = $passport->getBadge(\App\Auth\EmailVerifiedBadge::class);
        if ($badge && ! $badge->isResolved()) {
            $badge->markResolved();
        }
    }
}
```

**可用方法**：
- `getPassport(): Passport` — 获取当前 Passport
- `getRequest(): ServerRequestInterface` — 获取当前请求
- `getAuthenticator(): AuthenticatorInterface` — 获取触发认证的认证器

---

### AuthenticationSuccessEvent

**触发时机**：认证成功、Token 已创建之后（保存到 TokenStorage 之前）。

**用途**：记录审计日志、发送通知、替换 Token（如升级为双因素认证 Token）。

```php
use GaaraHyperf\Event\AuthenticationSuccessEvent;

class LoginAuditListener
{
    public function handle(AuthenticationSuccessEvent $event): void
    {
        $this->logger->info('用户登录', [
            'guard'      => $event->getGuardName(),
            'user'       => $event->getToken()->getUserIdentifier(),
            'authenticator' => get_class($event->getAuthenticator()),
        ]);
    }
}
```

**可用方法**：
- `getGuardName(): string`
- `getToken(): TokenInterface` — 当前 Token
- `setToken(TokenInterface): void` — **替换 Token**（可用于将普通 Token 升级为 TwoFactorToken）
- `getPassport(): Passport`
- `getRequest(): ServerRequestInterface`
- `getResponse(): ?ResponseInterface` — 认证器返回的响应（可能为 null）
- `setResponse(?ResponseInterface): void` — 修改响应
- `getPreviousToken(): ?TokenInterface` — 认证前 TokenStorage 中存在的旧 Token
- `getAuthenticator(): AuthenticatorInterface`

---

### AuthenticationFailureEvent

**触发时机**：认证失败（抛出 `AuthenticationException` 后）。

**用途**：记录失败日志、发送告警、更新失败计数。

```php
use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Exception\InvalidCredentialsException;

class LoginFailureListener
{
    public function handle(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();
        if ($exception instanceof InvalidCredentialsException) {
            $this->alertService->notify('密码错误次数过多');
        }
    }
}
```

**可用方法**：
- `getGuardName(): string`
- `getException(): AuthenticationException`
- `getPassport(): ?Passport` — 认证失败前创建的 Passport（可能为 null）
- `getRequest(): ServerRequestInterface`
- `getResponse(): ?ResponseInterface`
- `setResponse(?ResponseInterface): void`
- `getAuthenticator(): AuthenticatorInterface`

---

### LogoutEvent

**触发时机**：请求路径匹配 `logout_path` 且 Token 存在时。

**用途**：撤销令牌、清除 Session、记录登出日志。

```php
use GaaraHyperf\Event\LogoutEvent;

class LogoutListener
{
    public function handle(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if ($token) {
            $this->logger->info('用户登出', [
                'user' => $token->getUserIdentifier(),
            ]);
        }

        // 设置登出响应
        $event->setResponse($this->createLogoutResponse());
    }
}
```

**可用方法**：
- `getGuardName(): string`
- `getToken(): ?TokenInterface`
- `getRequest(): ServerRequestInterface`
- `getResponse(): ?ResponseInterface`
- `setResponse(?ResponseInterface): void`

---

## 内置监听器

内置监听器通过 Guard 的 `listeners` 配置项注册，仅作用于对应 Guard。

### IPWhiteListListener（IP 白名单）

在 `CheckPassportEvent` 中验证客户端 IP 是否在白名单内。

```php
[
    'class'  => \GaaraHyperf\EventListener\IPWhiteListListener::class,
    'params' => [
        'white_list' => [
            '127.0.0.1',
            '192.168.1.0/24',    // CIDR
            '10.0.*.*',          // 通配符
        ],
        // 或使用动态提供者：
        // 'white_list' => \App\Auth\DbIpWhiteListProvider::class,
    ],
],
```

IP 白名单支持三种格式：
- 精确 IP：`192.168.1.100`
- CIDR 网段：`172.16.0.0/12`
- 通配符：`10.*.*.* `

IP 解析优先级：`CF-Connecting-IP` > `X-Real-IP` > `X-Forwarded-For` > `remote_addr`。

---

### LoginAttemptLimitListener（登录限频）

在 `CheckPassportEvent` 中对登录请求进行限流，防止暴力破解。限流 key 为 `IP + 用户标识符`。

```php
[
    'class'  => \GaaraHyperf\EventListener\LoginAttemptLimitListener::class,
    'params' => [
        'type'    => 'sliding_window', // token_bucket | sliding_window | fixed_window
        'options' => [
            'prefix'   => 'login_limit',
            'limit'    => 5,
            'interval' => 300,  // sliding_window/fixed_window：时间窗口（秒）
            // 'rate' => 1.0,   // token_bucket：每秒生成令牌数
        ],
    ],
],
```

三种限流算法：

| 算法 | 特点 | 适用场景 |
|------|------|---------|
| `sliding_window` | 平滑，无边界效应 | 推荐用于登录限制 |
| `fixed_window` | 简单，有窗口边界突刺 | 适合简单计数场景 |
| `token_bucket` | 支持突发流量 | 适合 API 限流 |

认证失败时（`AuthenticationFailureEvent`）自动计数；成功时（`AuthenticationSuccessEvent`）自动重置计数器。

---

### PasswordExpirationListener（密码过期）

在认证成功后（`AuthenticationSuccessEvent`）检查用户密码是否过期。

用户模型须实现 `PasswordExpirationAwareUserInterface`（返回 `getExpiresAt(): DateTimeInterface`）。

```php
[
    'class'  => \GaaraHyperf\EventListener\PasswordExpirationListener::class,
    'params' => [
        'excluded_paths' => ['^/api/password/change$'],  // 不检查的路径
        'warning_days'   => 7,                           // 过期前 N 天发出警告（触发 PasswordExpiredException）
    ],
],
```

---

### OpaqueTokenRevokeLogoutListener（登出时撤销令牌）

在 `LogoutEvent` 触发时撤销当前 Opaque Token，防止令牌被重用。

```php
[
    'class'  => \GaaraHyperf\EventListener\OpaqueTokenRevokeLogoutListener::class,
    'params' => [
        'token_manager' => 'default',  // 使用哪个令牌管理器
    ],
],
```

---

### AuditLogListener（审计日志）

记录登录成功、失败、登出事件，包含 IP、User-Agent、时间戳等信息。

```php
[
    'class' => \GaaraHyperf\EventListener\AuditLogListener::class,
],
```

日志通过 Hyperf 的 Logger 系统输出，默认使用 `default` 日志通道。

---

### PasswordBadgeCheckListener（密码校验）

内置监听器，**由库自动注册**，无需手动配置。

在 `CheckPassportEvent` 中验证 `PasswordBadge`（调用 `PasswordHasher` 比对密码哈希）。`FormLoginAuthenticator` 和 `JsonLoginAuthenticator` 会自动向 Passport 添加 `PasswordBadge`。

---

### CsrfTokenBadgeCheckListener（CSRF 校验）

内置监听器，**由库自动注册**，无需手动配置。

在 `CheckPassportEvent` 中验证 `CsrfTokenBadge`。`FormLoginAuthenticator` 在启用 CSRF 保护时会自动添加 `CsrfTokenBadge`。

---

## 注册自定义监听器

在 Guard 的 `listeners` 中配置，监听器会订阅所有四个事件：

```php
'listeners' => [
    \App\Auth\MyListener::class,
],
```

监听器类需实现对应的事件处理方法（方法名不限，须接受对应事件类型参数），并通过 Hyperf 的事件系统（`EventSubscriberInterface` 或注解）进行订阅。

> **提示**：监听器按配置顺序触发。`CheckPassportEvent` 中抛出异常会立即中止认证流程并触发 `AuthenticationFailureEvent`。
