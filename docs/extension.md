# 扩展指南

本库的所有核心组件均基于接口设计，可以通过实现对应接口来替换或扩展任意部分。

---

## 自定义认证器

### 实现 AuthenticatorInterface

```php
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SmsCodeAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private \App\Service\SmsService $smsService,
        private \GaaraHyperf\UserProvider\UserProviderInterface $userProvider,
    ) {}

    /**
     * 判断当前请求是否由此认证器处理。
     * 此方法应轻量，避免执行耗时操作。
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === '/api/sms-login'
            && $request->getMethod() === 'POST';
    }

    /**
     * 执行认证逻辑，返回 Passport 或抛出 AuthenticationException。
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $body   = (array) json_decode((string) $request->getBody(), true);
        $phone  = $body['phone'] ?? '';
        $code   = $body['code']  ?? '';

        if (! $this->smsService->verifyCode($phone, $code)) {
            throw new \GaaraHyperf\Exception\InvalidCredentialsException('验证码错误');
        }

        $userIdentifier = $phone;

        return new Passport(
            userIdentifier: $userIdentifier,
            userLoader: fn() => $this->userProvider->findByIdentifier($userIdentifier),
        );
    }

    /**
     * 根据 Passport 创建 Token。
     * 通常直接使用 AuthenticatedToken。
     */
    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new \GaaraHyperf\Token\AuthenticatedToken(
            guardName: $guardName,
            userIdentifier: $passport->getUserIdentifier(),
        );
    }

    public function onAuthenticationSuccess(
        string $guardName,
        ServerRequestInterface $request,
        TokenInterface $token,
        Passport $passport,
    ): ?ResponseInterface {
        return null; // 返回 null 表示由框架继续处理请求
    }

    public function onAuthenticationFailure(
        string $guardName,
        ServerRequestInterface $request,
        \GaaraHyperf\Exception\AuthenticationException $exception,
        ?Passport $passport,
    ): ?ResponseInterface {
        return null; // 返回 null 则交给 UnauthenticatedHandler 处理
    }

    public function isInteractive(): bool
    {
        return true; // 交互式认证器（登录接口）
    }
}
```

### 继承 AbstractAuthenticator（推荐）

`AbstractAuthenticator` 已处理 `successHandler`/`failureHandler` 的委托逻辑，继承后只需关注核心业务：

```php
use GaaraHyperf\Authenticator\AbstractAuthenticator;
use GaaraHyperf\Authenticator\AuthenticationSuccessHandlerInterface;
use GaaraHyperf\Authenticator\AuthenticationFailureHandlerInterface;

class SmsCodeAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private \App\Service\SmsService $smsService,
        private \GaaraHyperf\UserProvider\UserProviderInterface $userProvider,
        ?AuthenticationSuccessHandlerInterface $successHandler = null,
        ?AuthenticationFailureHandlerInterface $failureHandler = null,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    public function supports(ServerRequestInterface $request): bool { ... }

    public function authenticate(ServerRequestInterface $request): Passport { ... }

    public function isInteractive(): bool { return true; }
}
```

### 在配置中注册

```php
'authenticators' => [
    'custom' => [
        [
            'class'  => \App\Auth\SmsCodeAuthenticator::class,
            'params' => [],  // 额外构造参数（DI 容器已注入的依赖无需列出）
        ],
    ],
],
```

---

## 自定义用户提供者

实现 `UserProviderInterface`：

```php
use GaaraHyperf\UserProvider\UserProviderInterface;
use GaaraHyperf\User\UserInterface;

class RedisUserProvider implements UserProviderInterface
{
    public function __construct(
        private \Hyperf\Redis\Redis $redis,
    ) {}

    public function findByIdentifier(string $identifier): ?UserInterface
    {
        $data = $this->redis->hGetAll("user:{$identifier}");
        if (empty($data)) {
            return null;
        }
        return new \App\Auth\RedisUser($data);
    }
}
```

配置：

```php
'user_provider' => [
    'type'  => 'custom',
    'class' => \App\Auth\RedisUserProvider::class,
],
```

---

## 自定义用户对象

实现相应接口以启用对应功能：

```php
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\User\PasswordAwareUserInterface;
use GaaraHyperf\User\PasswordExpirationAwareUserInterface;

class AppUser implements UserInterface, PasswordAwareUserInterface, PasswordExpirationAwareUserInterface
{
    public function __construct(private array $data) {}

    // 必须：用户唯一标识符（用于存储到 Token 中）
    public function getIdentifier(): string
    {
        return $this->data['email'];
    }

    // PasswordAwareUserInterface：启用密码验证
    public function getPassword(): string
    {
        return $this->data['password'];
    }

    // PasswordExpirationAwareUserInterface：启用密码过期检查
    public function getExpiresAt(): \DateTimeInterface
    {
        return new \DateTimeImmutable($this->data['password_changed_at'] . ' +90 days');
    }
}
```

---

## 自定义认证成功/失败处理器

### 成功处理器

```php
use GaaraHyperf\Authenticator\AuthenticationSuccessHandlerInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\Passport\Passport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonTokenSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function handle(
        string $guardName,
        ServerRequestInterface $request,
        TokenInterface $token,
        Passport $passport,
    ): ?ResponseInterface {
        $response = new \Hyperf\HttpMessage\Server\Response();
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new \GaaraHyperf\..., json_encode([
                'user' => $token->getUserIdentifier(),
                'guard' => $guardName,
            ]));
    }
}
```

### 失败处理器

```php
use GaaraHyperf\Authenticator\AuthenticationFailureHandlerInterface;
use GaaraHyperf\Exception\AuthenticationException;

class JsonFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function handle(
        string $guardName,
        ServerRequestInterface $request,
        AuthenticationException $exception,
        ?Passport $passport,
    ): ?ResponseInterface {
        // 返回 null 则交给 UnauthenticatedHandler 继续处理
        return null;
    }
}
```

在认证器中配置：

```php
'json_login' => [
    'check_path'      => '/api/login',
    'success_handler' => \App\Auth\JsonTokenSuccessHandler::class,
    'failure_handler' => \App\Auth\JsonFailureHandler::class,
],
```

---

## 自定义事件监听器

监听器会基于symfony/event-dispatcher组件的事件订阅机制实现的，在认证各阶段触发。

```php
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuditLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => ['onAuthenticationSuccess', Priority::LOW],
            AuthenticationFailureEvent::class => ['onAuthenticationFailure', Priority::LOW],
        ];
    }

    // 监听认证成功事件
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $this->logger->info('登录成功', [
            'guard'      => $event->getGuardName(),
            'user'       => $event->getToken()->getUserIdentifier(),
            'ip'         => $event->getRequest()->getServerParams()['remote_addr'] ?? '',
        ]);
    }

    // 监听认证失败事件
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $this->logger->warning('登录失败', [
            'reason' => $event->getException()->getMessage(),
        ]);
    }
}
```

详见 [事件系统](events.md)。

---

## 自定义 IP 白名单提供者

当白名单需要动态从数据库/Redis 加载时，实现 `IPWhiteListProviderInterface`：

```php
use GaaraHyperf\IPWhiteListChecker\IPWhiteListProviderInterface;

class DbIpWhiteListProvider implements IPWhiteListProviderInterface
{
    public function __construct(
        private \App\Repository\IpRepository $repo,
    ) {}

    public function getWhiteList(): array
    {
        return $this->repo->getApprovedIps();
    }
}
```

配置：

```php
'listeners' => [
    [
        'class'  => \GaaraHyperf\EventListener\IPWhiteListListener::class,
        'params' => [
            'white_list' => \App\Auth\DbIpWhiteListProvider::class,
        ],
    ],
],
```

---

## 自定义授权检查器

实现 `AuthorizationCheckerInterface` 以接入 Casbin、Spatie Permission 等授权框架：

```php
use GaaraHyperf\Authorization\AuthorizationCheckerInterface;
use GaaraHyperf\Token\TokenInterface;

class CasbinAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function __construct(
        private \Casbin\Enforcer $enforcer,
    ) {}

    /**
     * @param mixed $object 访问对象（如路由路径、资源名称等）
     * @param mixed $action 访问动作（如 HTTP 方法、权限名称等），可为 null
     */
    public function check(TokenInterface $token, mixed $object, mixed $action = null): bool
    {
        $user = $token->getUserIdentifier();
        $obj  = $object ?? '*';
        $act  = $action ?? '*';
        return $this->enforcer->enforce($user, $obj, $act);
    }
}
```

配置：

```php
'authorization' => [
    'checker' => [
        'class' => \App\Auth\CasbinAuthorizationChecker::class,
    ],
],
```

---

## 自定义密码哈希器

```php
use GaaraHyperf\PasswordHasher\PasswordHasherInterface;

class LegacyMd5PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $password): string
    {
        return md5($password);
    }

    public function verify(string $password, string $hashedPassword): bool
    {
        return hash_equals(md5($password), $hashedPassword);
    }
}
```

配置：

```php
'services' => [
    'password_hashers' => [
        'legacy' => [
            'type'  => 'custom',
            'class' => \App\Auth\LegacyMd5PasswordHasher::class,
        ],
    ],
],
```

在 Guard 中指定：

```php
'password_hasher' => 'legacy',
```

---

## 自定义不透明令牌颁发器

如果你需要替换 Opaque Token 的签发、解析或撤销逻辑（例如接入数据库、远端会话服务或自定义存储），请实现 `OpaqueTokenIssuerInterface`。

`OpaqueTokenManager` 本身仍由框架组装，负责：
- 从请求中提取访问令牌
- 调用自定义 issuer 进行签发 / 解析 / 撤销
- 通过 responder 生成最终响应

```php
use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenIssuerInterface;
use GaaraHyperf\Token\TokenInterface;

class CustomOpaqueTokenIssuer implements OpaqueTokenIssuerInterface
{
    public function issue(TokenInterface $token): OpaqueToken
    {
        // 返回一个 OpaqueToken 对象
    }

    public function resolve(string $accessToken): ?OpaqueToken
    {
        // 根据 accessToken 解析出 OpaqueToken
    }

    public function revoke(string $accessToken): void
    {
        // 撤销 accessToken
    }
}
```

配置：

```php
'services' => [
    'opaque_token_managers' => [
        'custom' => [
            'type'  => 'custom',
            'class' => \App\Auth\CustomOpaqueTokenIssuer::class,
            'token_extractor' => [
                'type' => 'header',
                'field' => 'Authorization',
                'scheme' => 'Bearer',
            ],
            'token_responder' => [
                'type' => 'body',
            ],
        ],
    ],
],
```

---

## 自定义请求匹配器

```php
use GaaraHyperf\RequestMatcher\RequestMatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

class TenantRequestMatcher implements RequestMatcherInterface
{
    public function matchesPattern(ServerRequestInterface $request): bool
    {
        return str_starts_with($request->getUri()->getPath(), '/tenant/');
    }

    public function matchesLogout(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === '/tenant/logout';
    }

    public function matchesExcluded(ServerRequestInterface $request): bool
    {
        return false;
    }
}
```

配置：

```php
'matcher' => [
    'type'  => 'custom',
    'class' => \App\Auth\TenantRequestMatcher::class,
],
```
