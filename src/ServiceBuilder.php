<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorFactory;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorRegistry;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorResolver;
use Lzpeng\HyperfAuthGuard\Authenticator\Factory\ApiKeyAuthenticatorFactory;
use Lzpeng\HyperfAuthGuard\Authenticator\Factory\FormLoginAuthenticatorFactory;
use Lzpeng\HyperfAuthGuard\Authenticator\Factory\JsonLoginAuthenticatorFactory;
use Lzpeng\HyperfAuthGuard\Authenticator\Factory\OpaqueTokenAuthenticatorFactory;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerResolver;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerResolverInterface;
use Lzpeng\HyperfAuthGuard\Config\Config;
use Lzpeng\HyperfAuthGuard\EventListener\PasswordBadgeCheckListener;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandler;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolver;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolverInterface;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherFactory;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolver;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolverInterface;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherFactory;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolver;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolverInteface;
use Lzpeng\HyperfAuthGuard\Token\TokenContext;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageFactory;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerFactory;
use Lzpeng\HyperfAuthGuard\UserProvider\Factory\MemoryUserProviderFactory;
use Lzpeng\HyperfAuthGuard\UserProvider\Factory\ModelUserProviderFactory;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderFactory;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderRegistry;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 认证组件服务构建器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ServiceBuilder
{
    /**
     * @param Config $config
     * @param ContainerInterface $container
     */
    public function __construct(
        private Config $config,
        private ContainerInterface $container,
    ) {}

    public function build(): void
    {
        $guardMap = [];
        $matcherMap = [];
        $authorizationCheckerMap = [];
        $logoutHandlerMap = [];
        $passwordHasherMap = [];
        foreach ($this->config->guardConfigCollection() as $guardConfig) {
            $guardName = $guardConfig->name();

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

            $passwordHasherId = sprintf('auth.guards.%s.password_hasher', $guardName);
            $passwordHasherMap[$guardName] = $passwordHasherId;
            $passwordHasherConfig = $guardConfig->passwordHasherConfig();
            $this->container->define($passwordHasherId, function () use ($passwordHasherConfig) {
                return $this->container->get(PasswordHasherFactory::class)->create($passwordHasherConfig);
            });

            $tokenStorageId = sprintf('auth.guards.%s.token_storage', $guardName);
            $tokenStorageConfig = $guardConfig->tokenStorageConfig();
            $this->container->define($tokenStorageId, function () use ($tokenStorageConfig) {
                return $this->container->get(TokenStorageFactory::class)->create($tokenStorageConfig);
            });

            $this->container->define(TokenContextInterface::class, function () {
                return new TokenContext('auth');
            });

            $eventDispatcherId = sprintf('auth.guards.%s.event_dispatcher', $guardName);
            $this->container->define($eventDispatcherId, function () use ($passwordHasherId) {
                $eventDispatcher = new EventDispatcher();
                $eventDispatcher->addSubscriber(new PasswordBadgeCheckListener(
                    $this->container->get($passwordHasherId)
                ));

                return $eventDispatcher;
            });

            foreach ($guardConfig->listenerConfigCollection() as $listenerConfig) {
                $listener = $this->container->make($listenerConfig->class(), $listenerConfig->args());
                if (!$listener instanceof EventSubscriberInterface) {
                    throw new \LogicException(sprintf('%s must implement EventSubscriberInterface', $listenerConfig->class()));
                }

                /**
                 * @var EventDispatcher $eventDispatcher
                 */
                $eventDispatcher = $this->container->get($eventDispatcherId);
                $eventDispatcher->addSubscriber($listener);
            }

            $authenticatorIds = [];
            foreach ($guardConfig->authenticatorConfigCollection() as $authenticatorConfig) {
                $authenticatorId = sprintf('auth.guards.%s.authenticators.%s', $guardName, $authenticatorConfig->type());
                $authenticatorIds[] = $authenticatorId;
                $this->container->define($authenticatorId, function () use ($authenticatorConfig, $userProviderId, $eventDispatcherId) {
                    return $this->container->get(AuthenticatorFactory::class)->create($authenticatorConfig, $userProviderId, $eventDispatcherId);
                });
            }
            $authenticatorResolverId = sprintf('auth.guards.%s.authenticator_resolver', $guardName);
            $this->container->define($authenticatorResolverId, function () use ($authenticatorIds) {
                return new AuthenticatorResolver($this->container, $authenticatorIds);
            });

            $unauthenticatedHandlerId = sprintf('auth.guards.%s.unauthenticated_handler', $guardName);
            $unauthenticatedHandlerConfig = $guardConfig->unauthenticatedHandlerConfig();
            $this->container->define($unauthenticatedHandlerId, function () use ($unauthenticatedHandlerConfig) {
                return $this->container->get(UnauthenticatedHandlerFactory::class)->create($unauthenticatedHandlerConfig);
            });

            $authorizationCheckerId = sprintf('auth.guards.%s.authorization_checker', $guardName);
            $authorizationCheckerMap[$guardName] = $authorizationCheckerId;
            $authorizationCheckerConfig = $guardConfig->authorizationCheckerConfig();
            $this->container->define($authorizationCheckerId, function () use ($authorizationCheckerConfig) {
                return $this->container->make(
                    $authorizationCheckerConfig->class(),
                    $authorizationCheckerConfig->args()
                );
            });

            $accessDeniedHandlerId = sprintf('auth.guards.%s.access_denied_handler', $guardName);
            $accessDeniedHandlerConfig = $guardConfig->accessDeniedHandlerConfig();
            $this->container->define($accessDeniedHandlerId, function () use ($accessDeniedHandlerConfig) {
                return $this->container->make(
                    $accessDeniedHandlerConfig->class(),
                    $accessDeniedHandlerConfig->args()
                );
            });

            $logoutHandlerId = sprintf('auth.guards.%s.logout_handler', $guardName);
            $logoutHandlerMap[$guardName] = $logoutHandlerId;
            $logoutConfig = $guardConfig->logoutConfig();
            $this->container->define($logoutHandlerId, function () use ($logoutConfig, $tokenStorageId, $eventDispatcherId) {
                return new LogoutHandler(
                    config: $logoutConfig,
                    tokenStorage: $this->container->get($tokenStorageId),
                    tokenContext: $this->container->get(TokenContextInterface::class),
                    eventDispatcher: $this->container->get($eventDispatcherId),
                    response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
                );
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
            return new RequestMatcherResolver($this->container, $matcherMap);
        });

        $this->container->define(PasswordHasherResolverInterface::class, function () use ($passwordHasherMap) {
            return new PasswordHasherResolver($this->container, $passwordHasherMap);
        });

        $this->container->define(AuthorizationCheckerResolverInterface::class, function () use ($authorizationCheckerMap) {
            return new AuthorizationCheckerResolver($this->container, $authorizationCheckerMap);
        });

        $this->container->define(GuardResolverInterface::class, function () use ($guardMap) {
            return new GuardResolver($this->container, $guardMap);
        });

        $this->container->define(LogoutHandlerResolverInterface::class, function () use ($logoutHandlerMap) {
            return new LogoutHandlerResolver($this->container, $logoutHandlerMap);
        });

        $userProviderRegistry = $this->container->get(UserProviderRegistry::class);
        $userProviderRegistry->register('memory', MemoryUserProviderFactory::class);
        $userProviderRegistry->register('model', ModelUserProviderFactory::class);

        $authenticatorRegistry = $this->container->get(AuthenticatorRegistry::class);
        $authenticatorRegistry->register('from_login', FormLoginAuthenticatorFactory::class);
        $authenticatorRegistry->register('json_login', JsonLoginAuthenticatorFactory::class);
        $authenticatorRegistry->register('api_key', ApiKeyAuthenticatorFactory::class);
        $authenticatorRegistry->register('opaque_token', OpaqueTokenAuthenticatorFactory::class);
    }
}
