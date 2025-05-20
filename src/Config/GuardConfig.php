<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandler;
use Lzpeng\HyperfAuthGuard\Authorization\NullAuthorizationChecker;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandler;

/**
 * 认证守卫配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardConfig
{
    /**
     * @param string $name
     * @param RequestMatcherConfig $requestMatcherConfig
     * @param UserProviderConfig $userProviderConfig
     * @param AuthenticatorConfigCollection $authenticatorConfigCollection
     * @param TokenStorageConfig $tokenStorageConfig
     * @param LogoutConfig $logoutConfig
     * @param UnauthenticatedHandlerConfig $unauthenticatedHandlerConfig
     * @param AuthorizationCheckerConfig $authorizationCheckerConfig
     * @param AccessDeniedHandlerConfig $accessDeniedHandlerConfig
     * @param ListenerConfigCollection $listenerConfigCollection
     * @param PasswordHasherConfig $passwordHasherConfig
     */
    public function __construct(
        private string $name,
        private RequestMatcherConfig $requestMatcherConfig,
        private UserProviderConfig $userProviderConfig,
        private AuthenticatorConfigCollection $authenticatorConfigCollection,
        private TokenStorageConfig $tokenStorageConfig,
        private LogoutConfig $logoutConfig,
        private UnauthenticatedHandlerConfig $unauthenticatedHandlerConfig,
        private AuthorizationCheckerConfig $authorizationCheckerConfig,
        private AccessDeniedHandlerConfig $accessDeniedHandlerConfig,
        private ListenerConfigCollection $listenerConfigCollection,
        private PasswordHasherConfig $passwordHasherConfig
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(string $name, array $config): self
    {
        $reqeustMatcherConfig = RequestMatcherConfig::from($config['matcher'] ??  throw new \InvalidArgumentException('matcher config is required'));
        $userProviderConfig = UserProviderConfig::from($config['user_provider'] ?? throw new \InvalidArgumentException('user_provider config is required'));
        $authenticatorConfigCollection = AuthenticatorConfigCollection::from($config['authenticators'] ?? throw new \InvalidArgumentException('authenticators config is required'));
        $tokenStorageConfig = TokenStorageConfig::from($config['token_storage'] ?? [
            'null' => []
        ]);
        $logoutConfig = LogoutConfig::from($config['logout'] ?? throw new \InvalidArgumentException('logout config is required'));
        $unauthenticatedHandlerConfig = UnauthenticatedHandlerConfig::from($config['unauthenticated_handler'] ?? [
            'class' => UnauthenticatedHandler::class,
        ]);
        $authorizationCheckerConfig = AuthorizationCheckerConfig::from($config['authorization_checker'] ?? [
            'class' => NullAuthorizationChecker::class,
        ]);
        $accessDeniedHandlerConfig = AccessDeniedHandlerConfig::from($config['access_denied_handler'] ?? [
            'class' => AccessDeniedHandler::class,
        ]);
        $listenerConfigCollection = ListenerConfigCollection::from($config['listeners'] ?? []);
        $passwordHasherConfig = PasswordHasherConfig::from($config['password_hasher'] ?? [
            'default' => [
                'algo' => PASSWORD_BCRYPT,
            ],
        ]);

        return new self(
            $name,
            $reqeustMatcherConfig,
            $userProviderConfig,
            $authenticatorConfigCollection,
            $tokenStorageConfig,
            $logoutConfig,
            $unauthenticatedHandlerConfig,
            $authorizationCheckerConfig,
            $accessDeniedHandlerConfig,
            $listenerConfigCollection,
            $passwordHasherConfig,
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
     * 返回监听器配置集合
     *
     * @return ListenerConfigCollection
     */
    public function listenerConfigCollection(): ListenerConfigCollection
    {
        return $this->listenerConfigCollection;
    }

    /**
     * 返回密码哈希器配置
     * 
     * @return PasswordHasherConfig
     */
    public function passwordHasherConfig(): PasswordHasherConfig
    {
        return $this->passwordHasherConfig;
    }
}
