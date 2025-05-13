<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use Lzpeng\HyperfAuthGuar\TokenStorage\TokenStorageResolver;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorResolverInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerInterface;
use Lzpeng\HyperfAuthGuard\Config\AuthenticatorConfig;
use Lzpeng\HyperfAuthGuard\Config\Config;
use Lzpeng\HyperfAuthGuard\Config\MatcherConfig;
use Lzpeng\HyperfAuthGuard\Config\TokenStorageConfig;
use Lzpeng\HyperfAuthGuard\RquestMatcher\PatternRequestMatcher;
use Lzpeng\HyperfAuthGuard\RquestMatcher\PrefixRequestMatcher;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherInterface;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolver;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolverInteface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\NullTokenStorage;
use Lzpeng\HyperfAuthGuard\TokenStorage\SessionTokenStorage;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageResolverInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenContext;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
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
        $tokenStorageMap = [];
        foreach ($this->config->guardConfigCollection() as $guardName => $guardConfig) {
            $matcherId = sprintf('auth.guards.%s.matcher', $guardName);
            $matcherMap[$guardName] = $matcherId;
            $matcherConfig = $guardConfig->matcherConfig();
            $this->container->set($matcherId, function () use ($matcherConfig) {
                return $this->createRequestMatcher($matcherConfig);
            });

            $tokenStorageId = sprintf('auth.guards.%s.token_storage', $guardName);
            $tokenStorageMap[$guardName] = $tokenStorageId;
            $tokenStorageConfig = $guardConfig->tokenStorageConfig();
            $this->container->set($tokenStorageId, function () use ($tokenStorageConfig) {
                return $this->createTokenStorage($tokenStorageConfig);
            });

            $guardId = sprintf('auth.guards.%s', $guardName);
            $guardMap[$guardName] = $guardId;
            $this->container->set($guardId, function () use ($guardName) {
                return new Guard(
                    name: $guardName,
                    authenticatorResolver: $this->container->get(AuthenticatorResolverInterface::class),
                    tokenContext: $this->container->get(TokenContextInterface::class),
                    tokenStorageResolver: $this->container->get(TokenStorageResolverInterface::class),
                    unauthenticatedHandler: $this->container->get(UnauthenticatedHandlerInterface::class),
                    authorizationChecker: $this->container->get(AuthorizationCheckerInterface::class),
                    accessDeniedHandler: $this->container->get(AccessDeniedHandlerInterface::class),
                    eventDispatcher: $this->container->get(EventDispatcherInterface::class),
                );
            });
        }

        $this->container->set(TokenContextInterface::class, function () {
            return new TokenContext();
        });

        $this->container->set(TokenStorageResolverInterface::class, function () use ($tokenStorageMap) {
            return new TokenStorageResolver($tokenStorageMap, $this->container);
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
        $matcherType = $matcherConfig->type();
        $matcherValue = $matcherConfig->value();

        switch ($matcherType) {
            case 'pattern':
                return new PatternRequestMatcher($matcherValue);
            case 'prefix':
                return new PrefixRequestMatcher($matcherValue);
            case 'custom':
                if (!isset($matcherValue['class'])) {
                    throw new \InvalidArgumentException('Invalid request matcher config: custom matcher must have class');
                }
                return $this->container->make($matcherValue['class'], $matcherValue['params'] ?? []);
            default:
                throw new \InvalidArgumentException(sprintf('Invalid request matcher type: %s', $matcherType));
        }
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
        $params = $tokenStorageConfig->params();

        switch ($type) {
            case 'session':
                return new SessionTokenStorage(
                    $this->container->get(SessionInterface::class),
                    $params['prefix'] ?? 'auth.token'
                );
            case 'null':
                return new NullTokenStorage();
            case 'custom':
                if (!isset($tokenStorageConfig['class'])) {
                    throw new \InvalidArgumentException('Invalid token storage config: custom token storage must have class');
                }

                return $this->container->make($tokenStorageConfig['class'], $tokenStorageConfig['params'] ?? []);
            default:
                throw new \InvalidArgumentException(sprintf('Invalid token storage type: %s', $type));
        }
    }

    /**
     * 创建认证器
     *
     * @param AuthenticatorConfig $authenticatorConfig
     * @return AuthenticatorInterface
     */
    private function createAuthenticator(AuthenticatorConfig $authenticatorConfig): AuthenticatorInterface {}
}
