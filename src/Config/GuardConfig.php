<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandler;
use Lzpeng\HyperfAuthGuard\Authorization\NullAuthorizationChecker;

/**
 * 认证守卫配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardConfig
{
    public function __construct(
        private string $name,
        private RequestMatcherConfig $requestMatcherConfig,
        private TokenStorageConfig $tokenStorageConfig,
        private UserProviderConfig $userProviderConfig,
        private AuthenticatorConfigCollection $authenticatorConfigCollection,
        private LogoutConfig $logoutConfig,
        private ListenerConfigCollection $listenerConfigCollection,
        private AuthorizationCheckerConfig $authorizationCheckerConfig,
        private AccessDeniedHandlerConfig $accessDeniedHandlerConfig,
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(string $name, array $config): self
    {
        $reqeustMatcherConfig = RequestMatcherConfig::from($config['matcher'] ??  throw new \InvalidArgumentException('matcher config is required'));
        $userProviderConfig = UserProviderConfig::from($config['user_provider'] ?? throw new \InvalidArgumentException('user_provider config is required'));
        $authenticatorConfigCollection = AuthenticatorConfigCollection::from($config['authenticators'] ?? []);
        $logoutConfig = LogoutConfig::from($config['logout'] ?? []);
        $tokenStorageConfig = TokenStorageConfig::from($config['token_storage'] ?? [
            'session' => [
                'prefix' => 'auth.token'
            ]
        ]);
        $listenerConfigCollection = new ListenerConfigCollection($config['listeners'] ?? []);
        $authorizationCheckerConfig = AuthorizationCheckerConfig::from($config['authorization_checker'] ?? [
            'class' => NullAuthorizationChecker::class,
        ]);
        $accessDeniedHandlerConfig = AccessDeniedHandlerConfig::from($config['access_denied_handler'] ?? [
            'class' => AccessDeniedHandler::class,
        ]);

        return new self(
            $name,
            $reqeustMatcherConfig,
            $tokenStorageConfig,
            $userProviderConfig,
            $authenticatorConfigCollection,
            $logoutConfig,
            $listenerConfigCollection,
            $authorizationCheckerConfig,
            $accessDeniedHandlerConfig,
        );
    }

    /**
     * 返回守卫名称
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
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
     * 返回登出配置
     *
     * @return LogoutConfig
     */
    public function logoutConfig(): LogoutConfig
    {
        return $this->logoutConfig;
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
}
