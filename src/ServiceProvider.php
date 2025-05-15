<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Event\ListenerData;
use Hyperf\Event\ListenerProvider;
use Lzpeng\HyperfAuthGuar\TokenStorage\TokenStorageResolver;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorResolver;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandler;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerInterface;
use Lzpeng\HyperfAuthGuard\Config\AccessDeniedHandlerConfig;
use Lzpeng\HyperfAuthGuard\Config\AuthenticatorConfig;
use Lzpeng\HyperfAuthGuard\Config\AuthorizationCheckerConfig;
use Lzpeng\HyperfAuthGuard\Config\Config;
use Lzpeng\HyperfAuthGuard\Config\LogoutConfig;
use Lzpeng\HyperfAuthGuard\Config\PasswordHasherConfig;
use Lzpeng\HyperfAuthGuard\Config\RequestMatcherConfig;
use Lzpeng\HyperfAuthGuard\Config\TokenStorageConfig;
use Lzpeng\HyperfAuthGuard\Config\UserProviderConfig;
use Lzpeng\HyperfAuthGuard\EventListener\GuardFilteredListener;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandler;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerInterface;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolver;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolverInterface;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasher;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherInterface;
use Lzpeng\HyperfAuthGuard\RquestMatcher\PatternRequestMatcher;
use Lzpeng\HyperfAuthGuard\RquestMatcher\PrefixRequestMatcher;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherInterface;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolver;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolverInteface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\NullTokenStorage;
use Lzpeng\HyperfAuthGuard\TokenStorage\SessionTokenStorage;
use Lzpeng\HyperfAuthGuard\Token\TokenContext;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageResolverInterface;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandler;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\MemoryUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\ModelUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * 认证组件服务提供者
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ServiceProvider
{
    /**
     * @param Config $config
     * @param ContainerInterface $container
     */
    public function __construct(
        private Config $config,
        private ContainerInterface $container,
    ) {}

    /**
     * 注册
     *
     * @return void
     */
    public function register()
    {
        $guardMap = [];
        $matcherMap = [];
        $tokenStorageMap = [];
        $logoutHandlerMap = [];
        foreach ($this->config->guardConfigCollection() as $guardConfig) {
            $guardName = $guardConfig->name();

            $matcherId = sprintf('auth.guards.%s.request_matcher', $guardName);
            $matcherMap[$guardName] = $matcherId;
            $requestMatcherConfig = $guardConfig->requestMatcherConfig();
            $this->container->set($matcherId, function () use ($requestMatcherConfig) {
                return $this->createRequestMatcher($requestMatcherConfig);
            });

            $userProviderId = sprintf('auth.guards.%s.user_provider', $guardName);
            $userProviderConfig = $guardConfig->userProviderConfig();
            $this->container->set($userProviderId, function () use ($userProviderConfig) {
                return $this->createUserProvider($userProviderConfig);
            });

            $authenticatorMap = [];
            foreach ($guardConfig->authenticatorConfigCollection() as $authenticatorConfig) {
                $authenticatorId = sprintf('auth.guards.%s.authenticators.%s', $guardName, $authenticatorConfig->type());
                $authenticatorMap[$guardName][] = $authenticatorId;
                $this->container->set($authenticatorId, function () use ($authenticatorConfig, $userProviderId) {
                    return $this->createAuthenticator($authenticatorConfig, $userProviderId);
                });
            }

            $authenticatorResolverId = sprintf('auth.guards.%s.authenticator_resolver', $guardName);
            $this->container->set($authenticatorResolverId, function () use ($authenticatorMap) {
                return new AuthenticatorResolver($authenticatorMap, $this->container);
            });

            $tokenStorageId = sprintf('auth.guards.%s.token_storage', $guardName);
            $tokenStorageMap[$guardName] = $tokenStorageId;
            $tokenStorageConfig = $guardConfig->tokenStorageConfig();
            $this->container->set($tokenStorageId, function () use ($tokenStorageConfig) {
                return $this->createTokenStorage($tokenStorageConfig);
            });

            $logoutHandlerId = sprintf('auth.guards.%s.logout_handler', $guardName);
            $logoutHandlerMap[$guardName] = $logoutHandlerId;
            $logoutConfig = $guardConfig->logoutConfig();
            $this->container->set($logoutHandlerId, function () use ($logoutConfig) {
                return $this->createLogoutHandler($logoutConfig);
            });

            $authorizationCheckerId = sprintf('auth.guards.%s.authorization_checker', $guardName);
            $authorizationCheckerConfig = $guardConfig->authorizationCheckerConfig();
            $this->container->set($authorizationCheckerId, function () use ($authorizationCheckerConfig) {
                return $this->createAuthorizationChecker($authorizationCheckerConfig);
            });

            $accessDeniedHandlerId = sprintf('auth.guards.%s.access_denied_handler', $guardName);
            $accessDeniedHandlerConfig = $guardConfig->accessDeniedHandlerConfig();
            $this->container->set($accessDeniedHandlerId, function () use ($accessDeniedHandlerConfig) {
                return $this->createAccessDeniedHandler($accessDeniedHandlerConfig);
            });

            foreach ($guardConfig->listenerConfigCollection() as $listenerConfig) {
                $listenerId = sprintf('auth.guards.%s.listeners.%s', $guardName, $listenerConfig->class());
                $this->container->set($listenerId, function () use ($guardName, $listenerConfig) {
                    $listener = $this->container->make($listenerConfig->class(), $listenerConfig->params());

                    return new GuardFilteredListener($guardName, $listener);
                });

                $listenerProvider = $this->container->get(ListenerProviderInterface::class);
                if (!$listenerProvider instanceof ListenerProvider) {
                    throw new \RuntimeException('ListenerProvider not found');
                }

                /**
                 * @var ListenerInterface
                 */
                $listenerInstance = $this->container->get($listenerId);
                $events = $listenerInstance->listen();
                foreach ($events as $event) {
                    $listenerProvider->on($event, [$listenerInstance, 'process'], ListenerData::DEFAULT_PRIORITY + 1);
                }
            }

            $guardId = sprintf('auth.guards.%s', $guardName);
            $guardMap[$guardName] = $guardId;
            $this->container->set($guardId, function () use (
                $guardName,
                $tokenStorageId,
                $authenticatorResolverId,
                $authorizationCheckerId,
                $accessDeniedHandlerId
            ) {
                return new Guard(
                    name: $guardName,
                    authenticatorResolver: $this->container->get($authenticatorResolverId),
                    tokenContext: $this->container->get(TokenContextInterface::class),
                    tokenStorage: $this->container->get($tokenStorageId),
                    unauthenticatedHandler: $this->container->get(UnauthenticatedHandlerInterface::class),
                    authorizationChecker: $this->container->get($authorizationCheckerId),
                    accessDeniedHandler: $this->container->get($accessDeniedHandlerId),
                    eventDispatcher: $this->container->get(EventDispatcherInterface::class),
                );
            });
        }

        $this->container->set(TokenContextInterface::class, function () {
            return new TokenContext('auth');
        });

        $this->container->set(TokenStorageResolverInterface::class, function () use ($tokenStorageMap) {
            return new TokenStorageResolver($tokenStorageMap, $this->container);
        });

        $this->container->set(LogoutHandlerResolverInterface::class, function () use ($logoutHandlerMap) {
            return new LogoutHandlerResolver($logoutHandlerMap, $this->container);
        });

        $this->container->set(UnauthenticatedHandlerInterface::class, function () {
            // TODO: 注册strategies
            return new UnauthenticatedHandler();
        });

        $this->container->set(AccessDeniedHandlerInterface::class, function () {
            // TODO
            return new AccessDeniedHandler();
        });

        $this->container->set(RequestMatcherResolverInteface::class, function () use ($matcherMap) {
            return new RequestMatcherResolver($matcherMap, $this->container);
        });

        $this->container->set(GuardResolverInterface::class, function () use ($guardMap) {
            return new GuardResolver($guardMap, $this->container);
        });

        $passwordHasherConfig = $this->config->passwordHasherConfig();
        $this->container->set(PasswordHasherInterface::class, function () use ($passwordHasherConfig) {
            return $this->createPasswordHasher($passwordHasherConfig);
        });
    }


    /**
     * 创建请求匹配器
     *
     * @param RequestMatcherConfig $requestMatcherConfig
     * @return RequestMatcherInterface
     */
    private function createRequestMatcher(RequestMatcherConfig $requestMatcherConfig): RequestMatcherInterface
    {
        $type = $requestMatcherConfig->type();
        $options = $requestMatcherConfig->options();

        switch ($type) {
            case 'pattern':
                return new PatternRequestMatcher($options['expr'], $options['exclusion'] ?? []);
            case 'prefix':
                return new PrefixRequestMatcher($options['expr'], $options['exclusion'] ?? []);
            case 'custom':
                if (!isset($options['class'])) {
                    throw new \InvalidArgumentException("自定定义匹配器必须指定class选项");
                }

                return $this->container->make($options['class'], $options['params'] ?? []);
            default:
                throw new \InvalidArgumentException("不支持的匹配类型: {$type}");
        }
    }

    /**
     * 创建用户提供者
     *
     * @param UserProviderConfig $userProviderConfig
     * @return UserProviderInterface
     */
    private function createUserProvider(UserProviderConfig $userProviderConfig): UserProviderInterface
    {
        $type = $userProviderConfig->type();
        $options = $userProviderConfig->options();

        switch ($type) {
            case 'memory':
                if (!isset($options['users'])) {
                    throw new \InvalidArgumentException("memory类型的用户提供器必须配置users选项");
                }

                return new MemoryUserProvider($options['users']);
            case 'model':
                if (!isset($options['class'])) {
                    throw new \InvalidArgumentException("model类型的用户提供器必须配置class选项");
                }

                if (!isset($options['identifier'])) {
                    throw new \InvalidArgumentException("model类型的用户提供器必须配置identifier选项");
                }

                return new ModelUserProvider($options['class'], $options['identifier']);
            case 'custom':
                if (!isset($options['class'])) {
                    throw new \InvalidArgumentException("自定义类型的用户提供器必须配置class选项");
                }

                return $this->container->make($options['class'], $options['params'] ?? []);
            default:
                throw new \InvalidArgumentException("未支持的用户提供者类型: {$type}");
        }
    }

    /**
     * 创建登出处理器
     *
     * @param LogoutConfig $logoutConfig
     * @return LogoutHandlerInterface
     */
    private function createLogoutHandler(LogoutConfig $logoutConfig): LogoutHandlerInterface
    {
        return new LogoutHandler(
            config: $logoutConfig,
            tokenStorageResolver: $this->container->get(TokenStorageResolverInterface::class),
            tokenContext: $this->container->get(TokenContextInterface::class),
            eventDispatcher: $this->container->get(EventDispatcherInterface::class),
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            util: $this->container->get(Util::class),
        );
    }

    /**
     * 创建授权检查器
     *
     * @param AuthorizationCheckerConfig $authorizationCheckerConfig
     * @return AuthorizationCheckerInterface
     */
    private function createAuthorizationChecker(AuthorizationCheckerConfig $authorizationCheckerConfig): AuthorizationCheckerInterface
    {
        return $this->container->make(
            $authorizationCheckerConfig->class(),
            $authorizationCheckerConfig->params()
        );
    }

    /**
     * 创建拒绝访问处理器
     *
     * @param AccessDeniedHandlerConfig $accessDeniedHandlerConfig
     * @return AccessDeniedHandlerInterface
     */
    private function createAccessDeniedHandler(AccessDeniedHandlerConfig $accessDeniedHandlerConfig): AccessDeniedHandlerInterface
    {
        return $this->container->make(
            $accessDeniedHandlerConfig->class(),
            $accessDeniedHandlerConfig->params()
        );
    }

    /**
     * 创建Token存储器
     *
     * @param TokenStorageConfig $tokenStorageConfig
     * @return TokenStorageInterface
     */
    private function createTokenStorage(TokenStorageConfig $tokenStorageConfig): TokenStorageInterface
    {
        $type = $tokenStorageConfig->type();
        $options = $tokenStorageConfig->options();

        switch ($type) {
            case 'session':
                return $this->container->make(SessionTokenStorage::class, [
                    'prefix' => $options['prefix'] ?? 'auth.token',
                ]);
            case 'null':
                return $this->container->get(NullTokenStorage::class);
            case 'custom':
                if (!isset($tokenStorageConfig['class'])) {
                    throw new \InvalidArgumentException('Invalid token storage config: custom token storage must have class');
                }

                return $this->container->make($options['class'], $options['params'] ?? []);
            default:
                throw new \InvalidArgumentException(sprintf('Invalid token storage type: %s', $type));
        }
    }

    /**
     * 创建认证器
     *
     * @param AuthenticatorConfig $authenticatorConfig
     * @param string $userProviderId
     * @return AuthenticatorInterface
     */
    private function createAuthenticator(AuthenticatorConfig $authenticatorConfig, string $userProviderId): AuthenticatorInterface
    {
        $name = $authenticatorConfig->name();
        $params = $authenticatorConfig->params();

        switch ($name) {
            case 'form_login':
                return new FormLoginAuthenticator(
                    $this->container->get($userProviderId),
                    $params['login_path'],
                    $params['check_path'],
                );
            case 'json_login':
                return new JsonLoginAuthenticator(
                    $this->container->get($userProviderId),
                    $params['check_path'],
                    $params['success_handler'] ?? null,
                );
            default:
                throw new \InvalidArgumentException(sprintf('Invalid authenticator name: %s', $name));
        }
    }

    /**
     * 创建密码哈希器
     *
     * @return PasswordHasherInterface
     */
    private function createPasswordHasher(PasswordHasherConfig $passwordHasherConfig): PasswordHasherInterface
    {
        $type = $passwordHasherConfig->type();
        $options = $passwordHasherConfig->options();

        switch ($type) {
            case 'default':
                return new PasswordHasher($options['algo'] ?? PASSWORD_BCRYPT);
            case 'custom':
                if (!isset($options['class'])) {
                    throw new \InvalidArgumentException();
                }

                $passwordHasher = $this->container->make($options['class'], $options['params'] ?? []);
                if (!$passwordHasher instanceof PasswordHasherInterface) {
                    throw new \InvalidArgumentException('Custom PasswordHasher class must be an instance of PasswordHasherInterface');
                }

                return $passwordHasher;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid password hasher type: %s', $type));
        }
    }
}
