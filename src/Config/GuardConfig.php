<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

use GaaraHyperf\Authorization\DefaultAccessDeniedHandler;
use GaaraHyperf\Authorization\NullAuthorizationChecker;
use InvalidArgumentException;

/**
 * 认证守卫配置.
 */
class GuardConfig
{
    public function __construct(
        private ComponentConfig $requestMatcherConfig,
        private ComponentConfig $userProviderConfig,
        private AuthenticatorConfigCollection $authenticatorConfigCollection,
        private ComponentConfig $tokenStorageConfig,
        private ComponentConfig $unauthenticatedHandlerConfig,
        private CustomConfig $authorizationCheckerConfig,
        private CustomConfig $accessDeniedHandlerConfig,
        private ListenerConfigCollection $listenerConfigCollection,
        private ?CustomConfig $authenticationTrustDeciderConfig,
        private string $passwordHasherId
    ) {
    }

    public static function from(array $config): self
    {
        $requestMatcherConfig = ComponentConfig::from($config['matcher'] ?? throw new InvalidArgumentException('matcher config is required'), 'default');
        $userProviderConfig = ComponentConfig::from($config['user_provider'] ?? throw new InvalidArgumentException('user_provider config is required'));
        $authenticatorConfigCollection = AuthenticatorConfigCollection::from($config['authenticators'] ?? throw new InvalidArgumentException('authenticators config is required'));
        $tokenStorageConfig = ComponentConfig::from($config['token_storage'] ?? ['type' => 'null']);
        $unauthenticatedHandlerConfig = ComponentConfig::from($config['unauthenticated_handler'] ?? ['type' => 'default']);
        $authorizationCheckerConfig = CustomConfig::from($config['authorization']['checker'] ?? ['class' => NullAuthorizationChecker::class]);
        $accessDeniedHandlerConfig = CustomConfig::from($config['authorization']['access_denied_handler'] ?? ['class' => DefaultAccessDeniedHandler::class]);
        $listenerConfigCollection = ListenerConfigCollection::from($config['listeners'] ?? []);
        $authenticationTrustDeciderConfig = null;
        if (isset($config['trust_decider'])) {
            $authenticationTrustDeciderConfig = CustomConfig::from($config['trust_decider']);
        }
        $passwordHasherId = $config['password_hasher'] ?? 'default';

        return new self(
            $requestMatcherConfig,
            $userProviderConfig,
            $authenticatorConfigCollection,
            $tokenStorageConfig,
            $unauthenticatedHandlerConfig,
            $authorizationCheckerConfig,
            $accessDeniedHandlerConfig,
            $listenerConfigCollection,
            $authenticationTrustDeciderConfig,
            $passwordHasherId,
        );
    }

    /**
     * 返回请求匹配器配置.
     */
    public function requestMatcherConfig(): ComponentConfig
    {
        return $this->requestMatcherConfig;
    }

    /**
     * 返回认证存储器配置.
     */
    public function tokenStorageConfig(): ComponentConfig
    {
        return $this->tokenStorageConfig;
    }

    /**
     * 返回用户提供者配置.
     */
    public function userProviderConfig(): ComponentConfig
    {
        return $this->userProviderConfig;
    }

    /**
     * 返回认证器配置集合.
     */
    public function authenticatorConfigCollection(): AuthenticatorConfigCollection
    {
        return $this->authenticatorConfigCollection;
    }

    /**
     * 返回未认证处理器配置.
     */
    public function unauthenticatedHandlerConfig(): ComponentConfig
    {
        return $this->unauthenticatedHandlerConfig;
    }

    /**
     * 返回授权检查器配置.
     */
    public function authorizationCheckerConfig(): CustomConfig
    {
        return $this->authorizationCheckerConfig;
    }

    /**
     * 返回拒绝访问处理器配置.
     */
    public function accessDeniedHandlerConfig(): CustomConfig
    {
        return $this->accessDeniedHandlerConfig;
    }

    /**
     * 返回监听器配置集合.
     */
    public function listenerConfigCollection(): ListenerConfigCollection
    {
        return $this->listenerConfigCollection;
    }

    /**
     * 返回认证信任决策器配置.
     */
    public function authenticationTrustDeciderConfig(): ?CustomConfig
    {
        return $this->authenticationTrustDeciderConfig;
    }

    /**
     * 返回密码哈希器ID.
     */
    public function passwordHasherId(): string
    {
        return $this->passwordHasherId;
    }
}
