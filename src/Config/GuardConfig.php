<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 认证守卫配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardConfig
{
    public function __construct(
        private MatcherConfig $matcherConfig,
        private TokenStorageConfig $tokenStorageConfig,
        private UserProviderConfig $userProviderConfig,
        private AuthenticatorConfigCollection $authenticatorConfigCollection,
        private LogoutConfig $logoutConfig,
        private ListenerConfigCollection $listenerConfigCollection,
    ) {}

    public static function from(array $config): self
    {
        $matcherConfig = MatcherConfig::from($config['matcher'] ?? []);
        $tokenStorageConfig = TokenStorageConfig::from($config['token_storage'] ?? [
            'session' => [
                'prefix' => 'auth.token'
            ]
        ]);
        $userProviderConfig = UserProviderConfig::from($config['user_provider'] ?? []);
        $authenticatorConfigCollection = AuthenticatorConfigCollection::from($config['authenticators'] ?? []);
        $logoutConfig = LogoutConfig::from($config['logout'] ?? []);
        $listenerConfigCollection = new ListenerConfigCollection($config['listeners'] ?? []);

        return new self(
            $matcherConfig,
            $tokenStorageConfig,
            $userProviderConfig,
            $authenticatorConfigCollection,
            $logoutConfig,
            $listenerConfigCollection,
        );
    }

    /**
     * 返回请求匹配器配置
     *
     * @return MatcherConfig
     */
    public function matcherConfig(): MatcherConfig
    {
        return $this->matcherConfig;
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
     * 是否无状态认证
     *
     * @return boolean
     */
    public function stateless(): bool
    {
        return $this->stateless;
    }
}
