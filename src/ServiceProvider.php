<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorResolver;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorResolverInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandler;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerInterface;
use Lzpeng\HyperfAuthGuard\Config\AuthenticatorConfig;
use Lzpeng\HyperfAuthGuard\Config\AuthorizationCheckerConfig;
use Lzpeng\HyperfAuthGuard\Config\Config;
use Lzpeng\HyperfAuthGuard\Config\MatcherConfig;
use Lzpeng\HyperfAuthGuard\Config\TokenStorageConfig;
use Lzpeng\HyperfAuthGuard\Config\UserProviderConfig;
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
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandler;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

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
        foreach ($this->config->guardConfigCollection() as $guardName => $guardConfig) {
            $matcherId = sprintf('auth.guards.%s.matcher', $guardName);
            $matcherMap[$guardName] = $matcherId;
            $matcherConfig = $guardConfig->matcherConfig();
            $this->container->set($matcherId, function () use ($matcherConfig) {
                return $this->createRequestMatcher($matcherConfig);
            });

            $tokenStorageId = sprintf('auth.guards.%s.token_storage', $guardName);
            $tokenStorageConfig = $guardConfig->tokenStorageConfig();
            $this->container->set($tokenStorageId, function () use ($tokenStorageConfig) {
                return $this->createTokenStorage($tokenStorageConfig);
            });

            $userProviderId = sprintf('auth.guards.%s.user_provider', $guardName);
            $userProviderConfig = $guardConfig->userProviderConfig();
            $this->container->set($userProviderId, function () use ($userProviderConfig) {
                return $this->createUserProvider($userProviderConfig);
            });

            $authenticatorMap = [];
            foreach ($guardConfig->authenticatorConfigCollection() as $authenticatorConfig) {
                $authenticatorId = sprintf('auth.guards.%s.authenticators.%s', $guardName, $authenticatorConfig->name());
                $authenticatorMap[$guardName][] = $authenticatorId;
                $this->container->set($authenticatorId, function () use ($authenticatorConfig, $userProviderId) {
                    return $this->createAuthenticator($authenticatorConfig, $userProviderId);
                });
            }

            $authenticatorResolverId = sprintf('auth.guards.%s.authenticator_resolver', $guardName);
            $this->container->set($authenticatorResolverId, function () use ($authenticatorMap) {
                return new AuthenticatorResolver($authenticatorMap, $this->container);
            });

            $authorizationCheckerId = sprintf('auth.guards.%s.authorization_checker', $guardName);
            $authorizationCheckerConfig = $guardConfig->authorizationCheckerConfig();
            $this->container->set($authorizationCheckerId, function () use ($authorizationCheckerConfig) {
                return $this->createAuthorizationChecker($authorizationCheckerConfig);
            });

            $guardId = sprintf('auth.guards.%s', $guardName);
            $guardMap[$guardName] = $guardId;
            $this->container->set($guardId, function () use ($guardName, $tokenStorageId, $authenticatorResolverId, $authorizationCheckerId) {
                return new Guard(
                    name: $guardName,
                    authenticatorResolver: $this->container->get($authenticatorResolverId),
                    tokenContext: $this->container->get(TokenContextInterface::class),
                    tokenStorage: $this->container->get($tokenStorageId),
                    unauthenticatedHandler: $this->container->get(UnauthenticatedHandlerInterface::class),
                    authorizationChecker: $this->container->get($authorizationCheckerId),
                    accessDeniedHandler: $this->container->get(AccessDeniedHandlerInterface::class),
                    eventDispatcher: $this->container->make(EventDispatcherInterface::class),
                );
            });
        }

        $this->container->set(TokenContextInterface::class, function () {
            return new TokenContext('auth');
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
    }

    /**
     * 创建请求匹配器
     *
     * @param MatcherConfig $matcherConfig
     * @return RequestMatcherInterface
     */
    private function createRequestMatcher(MatcherConfig $matcherConfig): RequestMatcherInterface
    {
        $type = $matcherConfig->type();
        $options = $matcherConfig->options();

        switch ($type) {
            case 'pattern':
                return new PatternRequestMatcher($options);
            case 'prefix':
                return new PrefixRequestMatcher($options);
            case 'custom':
                if (!isset($options['class'])) {
                    throw new \InvalidArgumentException('Invalid request matcher config: custom matcher must have class');
                }
                return $this->container->make($options['class'], $options['params'] ?? []);
            default:
                throw new \InvalidArgumentException(sprintf('Invalid request matcher type: %s', $type));
        }
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
                throw new \InvalidArgumentException(sprintf('Invalid user provider type: %s', $type));
            case 'custom':
                if (!isset($options['class'])) {
                    throw new \InvalidArgumentException('Invalid user provider config: custom user provider must have class');
                }

                return $this->container->make($options['class'], $options['params'] ?? []);
            default:
                throw new \InvalidArgumentException(sprintf('Invalid user provider type: %s', $type));
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
}
