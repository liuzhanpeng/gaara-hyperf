<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Event\EventDispatcher;
use Hyperf\Event\ListenerData;
use Hyperf\Event\ListenerProvider;
use Lzpeng\HyperfAuthGuard\Authenticator\ApiKeyAuthenticator;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorResolver;
use Lzpeng\HyperfAuthGuard\Authenticator\FormLogAuthenticator;
use Lzpeng\HyperfAuthGuard\Authenticator\JsonLoginAuthenticator;
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
use Lzpeng\HyperfAuthGuard\Config\UnauthenticatedHandlerConfig;
use Lzpeng\HyperfAuthGuard\Config\UserProviderConfig;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandler;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerInterface;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolver;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolverInterface;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasher;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherInterface;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolver;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolverInterface;
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
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\MemoryUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\ModelUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Lzpeng\HyperfAuthGuard\Util\Util;

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
        $logoutHandlerMap = [];
        $passwordHasherMap = [];
        $eventDispatcherMap = [];
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

            $authenticatorIds = [];
            foreach ($guardConfig->authenticatorConfigCollection() as $authenticatorConfig) {
                $authenticatorId = sprintf('auth.guards.%s.authenticators.%s', $guardName, $authenticatorConfig->type());
                $authenticatorIds[] = $authenticatorId;
                $this->container->set($authenticatorId, function () use ($authenticatorConfig, $userProviderId) {
                    return $this->createAuthenticator($authenticatorConfig, $userProviderId);
                });
            }
            $authenticatorResolverId = sprintf('auth.guards.%s.authenticator_resolver', $guardName);
            $this->container->set($authenticatorResolverId, function () use ($authenticatorIds) {
                return new AuthenticatorResolver($authenticatorIds, $this->container);
            });

            $tokenStorageId = sprintf('auth.guards.%s.token_storage', $guardName);
            $tokenStorageConfig = $guardConfig->tokenStorageConfig();
            $this->container->set($tokenStorageId, function () use ($tokenStorageConfig) {
                return $this->createTokenStorage($tokenStorageConfig);
            });

            $unauthenticatedHandlerId = sprintf('auth.guards.%s.unauthenticated_handler', $guardName);
            $unauthenticatedHandlerConfig = $guardConfig->unauthenticatedHandlerConfig();
            $this->container->set($unauthenticatedHandlerId, function () use ($unauthenticatedHandlerConfig) {
                $this->createUnauthenticatedHandler($unauthenticatedHandlerConfig);
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

            $listenerProvider = new ListenerProvider();
            foreach ($guardConfig->listenerConfigCollection() as $listenerConfig) {
                $listener = $this->container->make($listenerConfig->class(), $listenerConfig->params());
                if (!$listener instanceof ListenerInterface) {
                    throw new \LogicException(sprintf('%s must implement %s', $listenerConfig->class(), ListenerInterface::class));
                }

                foreach ($listener->listen() as $event) {
                    $listenerProvider->on($event, [$listener, 'handle'], ListenerData::DEFAULT_PRIORITY + 1);
                }
            }

            $eventDispatcherId = sprintf('auth.guards.%s.event_dispatcher', $guardName);
            $eventDispatcherMap[$guardName] = $eventDispatcherId;
            $this->container->set($eventDispatcherId, function () use ($listenerProvider) {
                $stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
                return new EventDispatcher($listenerProvider, $stdoutLogger);
            });

            $logoutHandlerId = sprintf('auth.guards.%s.logout_handler', $guardName);
            $logoutHandlerMap[$guardName] = $logoutHandlerId;
            $logoutConfig = $guardConfig->logoutConfig();
            $this->container->set($logoutHandlerId, function () use ($logoutConfig, $tokenStorageId, $eventDispatcherId) {
                return $this->createLogoutHandler($logoutConfig, $tokenStorageId, $eventDispatcherId);
            });

            $passwordHasherId = sprintf('auth.guards.%s.password_hasher', $guardName);
            $passwordHasherMap[$guardName] = $passwordHasherId;
            $passwordHasherConfig = $guardConfig->passwordHasherConfig();
            $this->container->set($passwordHasherId, function () use ($passwordHasherConfig) {
                return $this->createPasswordHasher($passwordHasherConfig);
            });

            $guardId = sprintf('auth.guards.%s', $guardName);
            $guardMap[$guardName] = $guardId;
            $this->container->set($guardId, function () use (
                $guardName,
                $authenticatorResolverId,
                $tokenStorageId,
                $unauthenticatedHandlerId,
                $authorizationCheckerId,
                $accessDeniedHandlerId,
                $eventDispatcherId,
            ) {
                return new Guard(
                    name: $guardName,
                    authenticatorResolver: $this->container->get($authenticatorResolverId),
                    tokenContext: $this->container->get(TokenContextInterface::class),
                    tokenStorage: $this->container->get($tokenStorageId),
                    unauthenticatedHandler: $this->container->get($unauthenticatedHandlerId),
                    authorizationChecker: $this->container->get($authorizationCheckerId),
                    accessDeniedHandler: $this->container->get($accessDeniedHandlerId),
                    eventDispatcher: $this->container->get($eventDispatcherId),
                );
            });
        }

        $this->container->set(TokenContextInterface::class, function () {
            return new TokenContext('auth');
        });

        $this->container->set(RequestMatcherResolverInteface::class, function () use ($matcherMap) {
            return new RequestMatcherResolver($matcherMap, $this->container);
        });

        $this->container->set(GuardResolverInterface::class, function () use ($guardMap) {
            return new GuardResolver($guardMap, $this->container);
        });

        $this->container->set(LogoutHandlerResolverInterface::class, function () use ($logoutHandlerMap) {
            return new LogoutHandlerResolver($logoutHandlerMap, $this->container);
        });

        $this->container->set(PasswordHasherResolverInterface::class, function () use ($passwordHasherMap) {
            return new PasswordHasherResolver($passwordHasherMap, $this->container);
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

                $requestMatcher = $this->container->make($options['class'], $options['params'] ?? []);
                if (!$requestMatcher instanceof RequestMatcherInterface) {
                    throw new \LogicException("自定义匹配器必须实现RequestMatcherInterface接口");
                }

                return $requestMatcher;
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

                $userProvider = $this->container->make($options['class'], $options['params'] ?? []);
                if (!$userProvider instanceof UserProviderInterface) {
                    throw new \LogicException("自定义类型的用户提供器必须实现UserProviderInterface接口");
                }

                return $userProvider;
            default:
                throw new \InvalidArgumentException("未支持的用户提供者类型: {$type}");
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
        $type = $authenticatorConfig->type();
        $options = $authenticatorConfig->options();

        switch ($type) {
            case 'form_login':
                return $this->createFormLoginAuthenticator($options, $userProviderId);
            case 'json_login':
                return $this->createJsonLoginAuthenticator($options, $userProviderId);
            case 'api_key':
                return $this->createApiKeyAuthenticator($options, $userProviderId);
            default:
                $authenticator = $this->container->make($type, $options['params'] ?? []);
                if (!$authenticator instanceof AuthenticatorInterface) {
                    throw new \LogicException();
                }

                return $authenticator;
        }
    }

    /**
     * 创建FormLogin认证器
     *
     * @param array $options
     * @param string $userProviderId
     * @return AuthenticatorInterface
     */
    private function createFormLoginAuthenticator(array $options, string $userProviderId): AuthenticatorInterface
    {
        $successHandler = null;
        if (isset($options['success_handler'])) {
            $successHandlerOption = $options['success_handler'];
            unset($options['success_handler']);
            if (is_string($successHandlerOption)) {
                $successHandlerOption = [
                    'class' => $options['success_handler']
                ];
            }

            $successHandler = $this->container->make(
                $successHandlerOption['class'],
                $successHandlerOption['params'] ?? []
            );
        }

        $failureHandler = null;
        if (isset($options['failure_handler'])) {
            $failureHandlerOption = $options['failure_handler'];
            unset($options['failure_handler']);
            if (is_string($failureHandlerOption)) {
                $failureHandlerOption = [
                    'class' => $options['failure_handler']
                ];
            }

            $failureHandler = $this->container->make(
                $failureHandlerOption['class'],
                $failureHandlerOption['params'] ?? []
            );
        }

        return new FormLogAuthenticator(
            options: $options,
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            userProvider: $this->container->get($userProviderId),
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            session: $this->container->get(SessionInterface::class)
        );
    }

    /**
     * 创建JsonLogin认证器
     *
     * @param array $options
     * @param string $userProviderId
     * @return JsonLoginAuthenticator
     */
    private function createJsonLoginAuthenticator(array $options, string $userProviderId): JsonLoginAuthenticator
    {
        $successHandler = null;
        if (isset($options['success_handler'])) {
            if (is_string($options['success_handler'])) {
                $options['success_handler'] = [
                    'class' => $options['success_handler']
                ];
            }

            $successHandler = $this->container->make(
                $options['success_handler']['class'],
                $options['success_handler']['params'] ?? []
            );
        }

        $failureHandler = null;
        if (isset($options['failure_handler'])) {
            if (is_string($options['failure_handler'])) {
                $options['failure_handler'] = [
                    'class' => $options['failure_handler']
                ];
            }

            $failureHandler = $this->container->make(
                $options['failure_handler']['class'],
                $options['failure_handler']['params'] ?? []
            );
        }

        return new JsonLoginAuthenticator(
            checkPath: $options['check_path'],
            usernameParam: $options['username_param'] ?? 'username',
            passwordParam: $options['password_param'] ?? 'password',
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            userProvider: $this->container->get($userProviderId),
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            util: $this->container->get(Util::class),
        );
    }

    private function createApiKeyAuthenticator(array $options, string $userProviderId): AuthenticatorInterface
    {
        $successHandler = null;
        if (isset($options['success_handler'])) {
            if (is_string($options['success_handler'])) {
                $options['success_handler'] = [
                    'class' => $options['success_handler']
                ];
            }

            $successHandler = $this->container->make(
                $options['success_handler']['class'],
                $options['success_handler']['params'] ?? []
            );
        }

        $failureHandler = null;
        if (isset($options['failure_handler'])) {
            if (is_string($options['failure_handler'])) {
                $options['failure_handler'] = [
                    'class' => $options['failure_handler']
                ];
            }

            $failureHandler = $this->container->make(
                $options['failure_handler']['class'],
                $options['failure_handler']['params'] ?? []
            );
        }

        return new ApiKeyAuthenticator(
            apiKeyParam: $options['api_key_param'] ?? 'X-API-Key',
            userProvider: $this->container->get($userProviderId),
            successHandler: $successHandler,
            failureHandler: $failureHandler
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
                return new NullTokenStorage();
            case 'custom':
                if (!isset($tokenStorageConfig['class'])) {
                    throw new \InvalidArgumentException("自定义Token存储器必须配置class选项");
                }

                $tokenStorage = $this->container->make($options['class'], $options['params'] ?? []);
                if (!$tokenStorage instanceof TokenStorageInterface) {
                    throw new \LogicException("自定义Token存储器必须实现TokenStorageInterface接口");
                }

                return $tokenStorage;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid token storage type: %s', $type));
        }
    }

    /**
     * 创建登出处理器
     *
     * @param LogoutConfig $logoutConfig
     * @param string $tokenStorageId
     * @param string $eventDispatcherId
     * @return LogoutHandlerInterface
     */
    private function createLogoutHandler(LogoutConfig $logoutConfig, string $tokenStorageId, string $eventDispatcherId): LogoutHandlerInterface
    {
        return new LogoutHandler(
            path: $logoutConfig->path(),
            target: $logoutConfig->target(),
            tokenStorage: $this->container->get($tokenStorageId),
            tokenContext: $this->container->get(TokenContextInterface::class),
            eventDispatcher: $this->container->get($eventDispatcherId),
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            util: $this->container->get(Util::class),
        );
    }

    /**
     * 创建未认证处理器
     *
     * @param UnauthenticatedHandlerConfig $unauthenticatedHandlerConfig
     * @return UnauthenticatedHandlerInterface
     */
    private function createUnauthenticatedHandler(UnauthenticatedHandlerConfig $unauthenticatedHandlerConfig): UnauthenticatedHandlerInterface
    {
        return $this->container->make(
            $unauthenticatedHandlerConfig->class(),
            $unauthenticatedHandlerConfig->params()
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
                    throw new \LogicException('Custom PasswordHasher class must be an instance of PasswordHasherInterface');
                }

                return $passwordHasher;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid password hasher type: %s', $type));
        }
    }
}
