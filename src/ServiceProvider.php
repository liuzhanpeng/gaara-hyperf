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
use Lzpeng\HyperfAuthGuard\Authenticator\OpaqueTokenAuthenticator;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerResolver;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerResolverInterface;
use Lzpeng\HyperfAuthGuard\Config\AccessDeniedHandlerConfig;
use Lzpeng\HyperfAuthGuard\Config\AuthenticatorConfig;
use Lzpeng\HyperfAuthGuard\Config\AuthorizationCheckerConfig;
use Lzpeng\HyperfAuthGuard\Config\Config;
use Lzpeng\HyperfAuthGuard\Config\LogoutConfig;
use Lzpeng\HyperfAuthGuard\Config\UnauthenticatedHandlerConfig;
use Lzpeng\HyperfAuthGuard\CsrfToken\CsrfTokenManager;
use Lzpeng\HyperfAuthGuard\CsrfToken\CsrfTokenManagerInterface;
use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Event\LogoutEvent;
use Lzpeng\HyperfAuthGuard\EventListener\CsrfTokenBadgeCheckListener;
use Lzpeng\HyperfAuthGuard\EventListener\OpaqueTokenLogoutListener;
use Lzpeng\HyperfAuthGuard\EventListener\PasswordBadgeCheckListener;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandler;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerInterface;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolver;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolverInterface;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuer;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerInterface;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolver;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolverInterface;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolver;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolverInteface;
use Lzpeng\HyperfAuthGuard\ServiceFactory\PasswordHasherFactory;
use Lzpeng\HyperfAuthGuard\ServiceFactory\RequestMatcherFactory;
use Lzpeng\HyperfAuthGuard\ServiceFactory\TokenStorageFactory;
use Lzpeng\HyperfAuthGuard\ServiceFactory\UserProviderFactory;
use Lzpeng\HyperfAuthGuard\Token\TokenContext;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
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
        $authorizationCheckerMap = [];
        $passwordHasherMap = [];
        $eventDispatcherMap = [];
        foreach ($this->config->guardConfigCollection() as $guardConfig) {
            $guardName = $guardConfig->name();

            $passwordHasherId = sprintf('auth.guards.%s.password_hasher', $guardName);
            $passwordHasherMap[$guardName] = $passwordHasherId;
            $passwordHasherConfig = $guardConfig->passwordHasherConfig();
            $this->container->define($passwordHasherId, function () use ($passwordHasherConfig) {
                return $this->container->get(PasswordHasherFactory::class)->create($passwordHasherConfig);
            });

            $this->container->define(PasswordHasherResolverInterface::class, function () use ($passwordHasherMap) {
                return new PasswordHasherResolver($passwordHasherMap, $this->container);
            });

            $this->container->define(CsrfTokenManagerInterface::class, function () {
                return $this->container->make(CsrfTokenManager::class);
            });

            $listenerProvider = new ListenerProvider();
            $listenerProvider->on(CheckPassportEvent::class, [$this->container->make(PasswordBadgeCheckListener::class), 'process'], ListenerData::DEFAULT_PRIORITY + 1);
            $listenerProvider->on(CheckPassportEvent::class, [$this->container->make(CsrfTokenBadgeCheckListener::class), 'process'], ListenerData::DEFAULT_PRIORITY + 1);

            $matcherId = sprintf('auth.guards.%s.request_matcher', $guardName);
            $matcherMap[$guardName] = $matcherId;
            $requestMatcherConfig = $guardConfig->requestMatcherConfig();
            $this->container->define($matcherId, function () use ($requestMatcherConfig) {
                return $this->container->get(RequestMatcherFactory::class)->create($requestMatcherConfig);
            });

            $userProviderId = sprintf('auth.guards.%s.user_provider', $guardName);
            $userProviderConfig = $guardConfig->userProviderConfig();
            $this->container->define($userProviderId, function () use ($userProviderConfig) {
                return $this->container->get(UserProviderFactory::class)->create($userProviderConfig);
            });

            $authenticatorIds = [];
            foreach ($guardConfig->authenticatorConfigCollection() as $authenticatorConfig) {
                $authenticatorId = sprintf('auth.guards.%s.authenticators.%s', $guardName, $authenticatorConfig->type());
                $authenticatorIds[] = $authenticatorId;
                $this->container->define($authenticatorId, function () use ($authenticatorConfig, $userProviderId, $listenerProvider) {
                    return $this->createAuthenticator($authenticatorConfig, $userProviderId, $listenerProvider);
                });
            }
            $authenticatorResolverId = sprintf('auth.guards.%s.authenticator_resolver', $guardName);
            $this->container->define($authenticatorResolverId, function () use ($authenticatorIds) {
                return new AuthenticatorResolver($authenticatorIds, $this->container);
            });

            $tokenStorageId = sprintf('auth.guards.%s.token_storage', $guardName);
            $tokenStorageConfig = $guardConfig->tokenStorageConfig();
            $this->container->define($tokenStorageId, function () use ($tokenStorageConfig) {
                return $this->container->get(TokenStorageFactory::class)->create($tokenStorageConfig);
            });

            $unauthenticatedHandlerId = sprintf('auth.guards.%s.unauthenticated_handler', $guardName);
            $unauthenticatedHandlerConfig = $guardConfig->unauthenticatedHandlerConfig();
            $this->container->define($unauthenticatedHandlerId, function () use ($unauthenticatedHandlerConfig) {
                return $this->createUnauthenticatedHandler($unauthenticatedHandlerConfig);
            });

            $authorizationCheckerId = sprintf('auth.guards.%s.authorization_checker', $guardName);
            $authorizationCheckerMap[$guardName] = $authorizationCheckerId;
            $authorizationCheckerConfig = $guardConfig->authorizationCheckerConfig();
            $this->container->define($authorizationCheckerId, function () use ($authorizationCheckerConfig) {
                return $this->createAuthorizationChecker($authorizationCheckerConfig);
            });

            $accessDeniedHandlerId = sprintf('auth.guards.%s.access_denied_handler', $guardName);
            $accessDeniedHandlerConfig = $guardConfig->accessDeniedHandlerConfig();
            $this->container->define($accessDeniedHandlerId, function () use ($accessDeniedHandlerConfig) {
                return $this->createAccessDeniedHandler($accessDeniedHandlerConfig);
            });


            $this->container->define(TokenContextInterface::class, function () {
                return new TokenContext('auth');
            });



            foreach ($guardConfig->listenerConfigCollection() as $listenerConfig) {
                $listener = $this->container->make($listenerConfig->class(), $listenerConfig->params());
                if (!$listener instanceof ListenerInterface) {
                    throw new \LogicException(sprintf('%s must implement %s', $listenerConfig->class(), ListenerInterface::class));
                }

                foreach ($listener->listen() as $event) {
                    $listenerProvider->on($event, [$listener, 'process'], ListenerData::DEFAULT_PRIORITY + 1);
                }
            }

            $eventDispatcherId = sprintf('auth.guards.%s.event_dispatcher', $guardName);
            $eventDispatcherMap[$guardName] = $eventDispatcherId;
            $this->container->define($eventDispatcherId, function () use ($listenerProvider) {
                $stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
                return new EventDispatcher($listenerProvider, $stdoutLogger);
            });

            $logoutHandlerId = sprintf('auth.guards.%s.logout_handler', $guardName);
            $logoutHandlerMap[$guardName] = $logoutHandlerId;
            $logoutConfig = $guardConfig->logoutConfig();
            $this->container->define($logoutHandlerId, function () use ($logoutConfig, $tokenStorageId, $eventDispatcherId) {
                return $this->createLogoutHandler($logoutConfig, $tokenStorageId, $eventDispatcherId);
            });

            $guardId = sprintf('auth.guards.%s', $guardName);
            $guardMap[$guardName] = $guardId;
            $this->container->define($guardId, function () use (
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

        $this->container->define(RequestMatcherResolverInteface::class, function () use ($matcherMap) {
            return new RequestMatcherResolver($matcherMap, $this->container);
        });

        $this->container->define(AuthorizationCheckerResolverInterface::class, function () use ($authorizationCheckerMap) {
            return new AuthorizationCheckerResolver($authorizationCheckerMap, $this->container);
        });

        $this->container->define(GuardResolverInterface::class, function () use ($guardMap) {
            return new GuardResolver($guardMap, $this->container);
        });

        $this->container->define(LogoutHandlerResolverInterface::class, function () use ($logoutHandlerMap) {
            return new LogoutHandlerResolver($logoutHandlerMap, $this->container);
        });

        $this->container->define(OpaqueTokenIssuerInterface::class, function () {
            return $this->container->make(OpaqueTokenIssuer::class, [
                'cachePrefix' => 'auth:opaque_token:'
            ]);
        });
    }

    /**
     * 创建认证器
     *
     * @param AuthenticatorConfig $authenticatorConfig
     * @param string $userProviderId
     * @param ListenerProvider $listenerProvider
     * @return AuthenticatorInterface
     */
    private function createAuthenticator(AuthenticatorConfig $authenticatorConfig, string $userProviderId, ListenerProvider $listenerProvider): AuthenticatorInterface
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
            case 'opaque_token':
                return $this->createOpaqueTokenAuthenticator($options, $listenerProvider);
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

        return new JsonLoginAuthenticator(
            options: $options,
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            userProvider: $this->container->get($userProviderId),
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            util: $this->container->get(Util::class),
        );
    }

    /**
     * 创建API Key认证器
     *
     * @param array $options
     * @param string $userProviderId
     * @return AuthenticatorInterface
     */
    private function createApiKeyAuthenticator(array $options, string $userProviderId): AuthenticatorInterface
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

        return new ApiKeyAuthenticator(
            options: $options,
            userProvider: $this->container->get($userProviderId),
            successHandler: $successHandler,
            failureHandler: $failureHandler
        );
    }

    private function createOpaqueTokenAuthenticator(array $options, ListenerProvider $listenerProvider): AuthenticatorInterface
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

        if (!isset($options['issuer'])) {
            $options['issuer'] = [
                'class' => OpaqueTokenIssuer::class,
                'params' => [
                    'cachePrefix' => 'auth:opaque_token:'
                ]
            ];
        }

        $listener = $this->container->make(OpaqueTokenLogoutListener::class, [
            'options' => [
                'header_param' => $options['header_param'],
                'token_type' => $options['token_type'],
            ]
        ]);

        $listenerProvider->on(LogoutEvent::class, [$listener, 'process'], ListenerData::DEFAULT_PRIORITY + 1);

        return new OpaqueTokenAuthenticator(
            options: $options,
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            issuer: $this->container->get(OpaqueTokenIssuerInterface::class)
        );
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
            $this->paramsCamelCase($unauthenticatedHandlerConfig->params())
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
     * @param array $params
     * @return array
     */
    private function paramsCamelCase(array $params): array
    {
        $result = [];
        foreach ($params as $key => $value) {
            $result[$this->toCamelCase($key)] = $value;
        }
        return $result;
    }

    /**
     * @param string $str
     * @return string
     */
    private function toCamelCase(string $str): string
    {
        $parts = explode('_', $str);
        if (count($parts) <= 1) {
            return $str;
        }

        $camelParts = array_map(function ($part) {
            return ucfirst($part);
        }, array_slice($parts, 1));

        return $parts[0] . implode('', $camelParts);
    }
}
