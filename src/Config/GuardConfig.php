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
     * @param ComponentConfig $requestMatcherConfig
     * @param ComponentConfig $userProviderConfig
     * @param AuthenticatorConfigCollection $authenticatorConfigCollection
     * @param ComponentConfig $tokenStorageConfig
     * @param ComponentConfig $unauthenticatedHandlerConfig
     * @param CustomConfig $authorizationCheckerConfig
     * @param CustomConfig $accessDeniedHandlerConfig
     * @param ComponentConfig $loginRateLimiterConfig
     * @param ListenerConfigCollection $listenerConfigCollection
     * @param string $passwordHasherId
     */
    public function __construct(
        private ComponentConfig $requestMatcherConfig,
        private ComponentConfig $userProviderConfig,
        private AuthenticatorConfigCollection $authenticatorConfigCollection,
        private ComponentConfig $tokenStorageConfig,
        private ComponentConfig $unauthenticatedHandlerConfig,
        private CustomConfig $authorizationCheckerConfig,
        private CustomConfig $accessDeniedHandlerConfig,
        private ComponentConfig $loginRateLimiterConfig,
        private ListenerConfigCollection $listenerConfigCollection,
        private string $passwordHasherId
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        $requestMatcherConfig = ComponentConfig::from($config['matcher'] ??  throw new \InvalidArgumentException('matcher config is required'), 'default');
        $userProviderConfig = ComponentConfig::from($config['user_provider'] ?? throw new \InvalidArgumentException('user_provider config is required'));
        $authenticatorConfigCollection = AuthenticatorConfigCollection::from($config['authenticators'] ?? throw new \InvalidArgumentException('authenticators config is required'));
        $tokenStorageConfig = ComponentConfig::from($config['token_storage'] ?? ['type' => 'null']);
        $unauthenticatedHandlerConfig = ComponentConfig::from($config['unauthenticated_handler'] ?? ['type' => 'default']);
        $authorizationCheckerConfig = CustomConfig::from($config['authorization']['checker'] ?? [
            'class' => NullAuthorizationChecker::class,
        ]);
        $accessDeniedHandlerConfig = CustomConfig::from($config['authorization']['access_denied_handler'] ?? [
            'class' => DefaultAccessDeniedHandler::class,
        ]);
        $loginRateLimiterConfig = ComponentConfig::from($config['login_rate_limiter'] ?? ['type' => 'no_limit']);
        $listenerConfigCollection = ListenerConfigCollection::from($config['listeners'] ?? []);
        $passwordHasherId = $config['password_hasher'] ?? 'default';

        return new self(
            $requestMatcherConfig,
            $userProviderConfig,
            $authenticatorConfigCollection,
            $tokenStorageConfig,
            $unauthenticatedHandlerConfig,
            $authorizationCheckerConfig,
            $accessDeniedHandlerConfig,
            $loginRateLimiterConfig,
            $listenerConfigCollection,
            $passwordHasherId,
        );
    }

    /**
     * 返回请求匹配器配置
     *
     * @return ComponentConfig
     */
    public function requestMatcherConfig(): ComponentConfig
    {
        return $this->requestMatcherConfig;
    }

    /**
     * 返回认证存储器配置
     *
     * @return ComponentConfig
     */
    public function tokenStorageConfig(): ComponentConfig
    {
        return $this->tokenStorageConfig;
    }

    /**
     * 返回用户提供者配置
     *
     * @return ComponentConfig
     */
    public function userProviderConfig(): ComponentConfig
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
     * @return ComponentConfig
     */
    public function unauthenticatedHandlerConfig(): ComponentConfig
    {
        return $this->unauthenticatedHandlerConfig;
    }

    /**
     * 返回授权检查器配置
     *
     * @return CustomConfig
     */
    public function authorizationCheckerConfig(): CustomConfig
    {
        return $this->authorizationCheckerConfig;
    }

    /**
     * 返回拒绝访问处理器配置
     *
     * @return CustomConfig
     */
    public function accessDeniedHandlerConfig(): CustomConfig
    {
        return $this->accessDeniedHandlerConfig;
    }

    /**
     * 返回登录限流器配置
     *
     * @return ComponentConfig
     */
    public function loginRateLimiterConfig(): ComponentConfig
    {
        return $this->loginRateLimiterConfig;
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
