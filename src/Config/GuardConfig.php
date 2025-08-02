<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

use Lzpeng\HyperfAuthGuard\Authorization\DefaultAccessDeniedHandler;
use Lzpeng\HyperfAuthGuard\Authorization\NullAuthorizationChecker;

/**
 * 认证守卫配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardConfig
{
    /**
     * @param RequestMatcherConfig $requestMatcherConfig
     * @param UserProviderConfig $userProviderConfig
     * @param AuthenticatorConfigCollection $authenticatorConfigCollection
     * @param TokenStorageConfig $tokenStorageConfig
     * @param UnauthenticatedHandlerConfig $unauthenticatedHandlerConfig
     * @param AuthorizationCheckerConfig $authorizationCheckerConfig
     * @param AccessDeniedHandlerConfig $accessDeniedHandlerConfig
     * @param LoginThrottlerConfig $loginThrottlerConfig
     * @param ListenerConfigCollection $listenerConfigCollection
     * @param string $passwordHasherId
     */
    public function __construct(
        private RequestMatcherConfig $requestMatcherConfig,
        private UserProviderConfig $userProviderConfig,
        private AuthenticatorConfigCollection $authenticatorConfigCollection,
        private TokenStorageConfig $tokenStorageConfig,
        private UnauthenticatedHandlerConfig $unauthenticatedHandlerConfig,
        private AuthorizationCheckerConfig $authorizationCheckerConfig,
        private AccessDeniedHandlerConfig $accessDeniedHandlerConfig,
        private LoginThrottlerConfig $loginThrottlerConfig,
        private ListenerConfigCollection $listenerConfigCollection,
        private string $passwordHasherId
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        $reqeustMatcherConfig = RequestMatcherConfig::from($config['matcher'] ??  throw new \InvalidArgumentException('matcher config is required'));
        $userProviderConfig = UserProviderConfig::from($config['user_provider'] ?? throw new \InvalidArgumentException('user_provider config is required'));
        $authenticatorConfigCollection = AuthenticatorConfigCollection::from($config['authenticators'] ?? throw new \InvalidArgumentException('authenticators config is required'));
        $tokenStorageConfig = TokenStorageConfig::from($config['token_storage'] ?? ['null' => []]);
        $unauthenticatedHandlerConfig = UnauthenticatedHandlerConfig::from($config['unauthenticated_handler'] ?? ['default' => []]);
        $authorizationCheckerConfig = AuthorizationCheckerConfig::from($config['authorization']['checker'] ?? [
            'class' => NullAuthorizationChecker::class,
        ]);
        $accessDeniedHandlerConfig = AccessDeniedHandlerConfig::from($config['authorization']['access_denied_handler'] ?? [
            'class' => DefaultAccessDeniedHandler::class,
        ]);
        $loginThrottlerConfig = LoginThrottlerConfig::from($config['login_throttler'] ?? [
            'sliding_window' => [
                'max_attempts' => 5,
                'interval' => 300,
            ]
        ]);
        $listenerConfigCollection = ListenerConfigCollection::from($config['listeners'] ?? []);
        $passwordHasherId = $config['password_hasher'] ?? 'default';

        return new self(
            $reqeustMatcherConfig,
            $userProviderConfig,
            $authenticatorConfigCollection,
            $tokenStorageConfig,
            $unauthenticatedHandlerConfig,
            $authorizationCheckerConfig,
            $accessDeniedHandlerConfig,
            $loginThrottlerConfig,
            $listenerConfigCollection,
            $passwordHasherId,
        );
    }

    /**
     * 返回请求匹配器配置
     *
     * @return RequestMatcherConfig
     */
    public function requestMatcherConfig(): RequestMatcherConfig
    {
        return $this->requestMatcherConfig;
    }

    /**
     * 返回认证存储器配置
     *
     * @return TokenStorageConfig
     */
    public function tokenStorageConfig(): TokenStorageConfig
    {
        return $this->tokenStorageConfig;
    }

    /**
     * 返回用户提供者配置
     *
     * @return UserProviderConfig
     */
    public function userProviderConfig(): UserProviderConfig
    {
        return $this->userProviderConfig;
    }

    /**
     * 返回认证器配置集合
     *
     * @return AuthenticatorConfigCollection
     */
    public function authenticatorConfigCollection(): AuthenticatorConfigCollection
    {
        return $this->authenticatorConfigCollection;
    }

    /**
     * 返回未认证处理器配置
     *
     * @return UnauthenticatedHandlerConfig
     */
    public function unauthenticatedHandlerConfig(): UnauthenticatedHandlerConfig
    {
        return $this->unauthenticatedHandlerConfig;
    }

    /**
     * 返回授权检查器配置
     *
     * @return AuthorizationCheckerConfig
     */
    public function authorizationCheckerConfig(): AuthorizationCheckerConfig
    {
        return $this->authorizationCheckerConfig;
    }

    /**
     * 返回拒绝访问处理器配置
     *
     * @return AccessDeniedHandlerConfig
     */
    public function accessDeniedHandlerConfig(): AccessDeniedHandlerConfig
    {
        return $this->accessDeniedHandlerConfig;
    }

    /**
     * 返回登录限流器配置
     *
     * @return LoginThrottlerConfig
     */
    public function loginThrottlerConfig(): LoginThrottlerConfig
    {
        return $this->loginThrottlerConfig;
    }

    /**
     * 返回监听器配置集合
     *
     * @return ListenerConfigCollection
     */
    public function listenerConfigCollection(): ListenerConfigCollection
    {
        return $this->listenerConfigCollection;
    }

    /**
     * 返回密码哈希器ID
     * 
     * @return string
     */
    public function passwordHasherId(): string
    {
        return $this->passwordHasherId;
    }
}
