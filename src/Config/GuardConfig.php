<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

use GaaraHyperf\Authorization\DefaultAccessDeniedHandler;
use GaaraHyperf\Authorization\HttpAuthorizationRuleResolver;
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
        private CustomConfig $authorizationRuleResolverConfig,
        private CustomConfig $authorizationCheckerConfig,
        private CustomConfig $accessDeniedHandlerConfig,
        private ListenerConfigCollection $listenerConfigCollection,
        private string $passwordHasherId
    ) {
    }

    public static function from(array $config, string $guardName = 'default'): self
    {
        $missing = static function (string $key, string $guardName): never {
            throw new InvalidArgumentException(sprintf(
                'Missing required config `guards.%s.%s`. See docs/quickstart.md for a minimal example and docs/configuration.md for full options.',
                $guardName,
                $key,
            ));
        };

        $requestMatcherConfig = ComponentConfig::from($config['matcher'] ?? $missing('matcher', $guardName), 'default');
        $userProviderConfig = ComponentConfig::from($config['user_provider'] ?? $missing('user_provider', $guardName));
        $authenticatorConfigCollection = AuthenticatorConfigCollection::from($config['authenticators'] ?? $missing('authenticators', $guardName));
        $tokenStorageConfig = ComponentConfig::from($config['token_storage'] ?? ['type' => 'null']);
        $unauthenticatedHandlerConfig = ComponentConfig::from($config['unauthenticated_handler'] ?? ['type' => 'default']);
        $authorizationConfig = $config['authorization'] ?? [];
        $authorizationRuleResolverConfig = CustomConfig::from($authorizationConfig['rule_resolver'] ?? ['class' => HttpAuthorizationRuleResolver::class]);
        $authorizationCheckerConfig = CustomConfig::from($authorizationConfig['checker'] ?? ['class' => NullAuthorizationChecker::class]);
        $accessDeniedHandlerConfig = CustomConfig::from($authorizationConfig['access_denied_handler'] ?? ['class' => DefaultAccessDeniedHandler::class]);
        $listenerConfigCollection = ListenerConfigCollection::from($config['listeners'] ?? []);
        $passwordHasherId = $config['password_hasher'] ?? 'default';

        return new self(
            $requestMatcherConfig,
            $userProviderConfig,
            $authenticatorConfigCollection,
            $tokenStorageConfig,
            $unauthenticatedHandlerConfig,
            $authorizationRuleResolverConfig,
            $authorizationCheckerConfig,
            $accessDeniedHandlerConfig,
            $listenerConfigCollection,
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
     * 返回授权规则解析器配置.
     */
    public function authorizationRuleResolverConfig(): CustomConfig
    {
        return $this->authorizationRuleResolverConfig;
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
     * 返回密码哈希器ID.
     */
    public function passwordHasherId(): string
    {
        return $this->passwordHasherId;
    }
}
