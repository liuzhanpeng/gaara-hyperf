<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorFactory;
use Lzpeng\HyperfAuthGuard\Config\ConfigLoaderInterface;
use Lzpeng\HyperfAuthGuard\Config\GuardConfig;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\EventListener\LoginRateLimitListener;
use Lzpeng\HyperfAuthGuard\EventListener\LoginThrottlingListener;
use Lzpeng\HyperfAuthGuard\EventListener\PasswordBadgeCheckListener;
use Lzpeng\HyperfAuthGuard\Guard;
use Lzpeng\HyperfAuthGuard\GuardInterface;
use Lzpeng\HyperfAuthGuard\GuardResolver;
use Lzpeng\HyperfAuthGuard\LoginRateLimiter\LoginRateLimiterFactory;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolverInterface;
use Lzpeng\HyperfAuthGuard\RequestMatcher\RequestMatcher;
use Lzpeng\HyperfAuthGuard\Token\TokenContext;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageFactory;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerFactory;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderFactory;
use Lzpeng\HyperfAuthGuard\Utils\IpResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 认证守卫服务提供者
 * 
 * 核心提供器，负责在容器中注册所有的认证守卫
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardServiceProvider implements ServiceProviderInterface
{
    /**
     * 注册服务
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container): void
    {
        // 注册内置的令牌上下文
        $container->define(TokenContextInterface::class, function () {
            return new TokenContext(Constants::TOKEN_CONTEXT_PREFIX);
        });

        $config = $container->get(ConfigLoaderInterface::class)->load();

        $guardMap = [];
        foreach ($config->guardConfigCollection() as $guardName => $guardConfig) {
            $guardMap[$guardName] = sprintf('%s.%s', Constants::GUARD_PREFIX, $guardName);

            $container->define($guardMap[$guardName], function () use ($container, $guardName, $guardConfig) {
                return $this->createGuard($container, $guardName, $guardConfig);
            });
        }

        $container->set(GuardResolver::class, new GuardResolver($guardMap, $container));
    }

    /**
     * 创建一个认证守卫实例
     *
     * @param ContainerInterface $container
     * @param string $guardName
     * @param GuardConfig $guardConfig
     * @return GuardInterface
     */
    private function createGuard(ContainerInterface $container, string $guardName, GuardConfig $guardConfig): GuardInterface
    {
        $requestMatcherConfig = $guardConfig->requestMatcherConfig();
        $requestMatcher = new RequestMatcher(
            $requestMatcherConfig->pattern(),
            $requestMatcherConfig->logoutPath(),
            $requestMatcherConfig->exclusions(),
            $requestMatcherConfig->cacheSize(),
        );

        $tokenStorage = $container->get(TokenStorageFactory::class)->create($guardConfig->tokenStorageConfig());

        $unauthenticatedHandler = $container->get(UnauthenticatedHandlerFactory::class)->create($guardConfig->unauthenticatedHandlerConfig());

        $authorizationCheckerConfig = $guardConfig->authorizationCheckerConfig();
        $authorizationChecker = $container->make($authorizationCheckerConfig->class(), $authorizationCheckerConfig->args());

        $accessDeniedHandlerConfig = $guardConfig->accessDeniedHandlerConfig();
        $accessDeniedHandler = $container->make($accessDeniedHandlerConfig->class(), $accessDeniedHandlerConfig->args());

        $eventDispatcher = new EventDispatcher();

        // 注册内置密码验证监听器
        $passwordHasher = $container->get(PasswordHasherResolverInterface::class)->resolve($guardConfig->passwordHasherId());
        $eventDispatcher->addSubscriber(new PasswordBadgeCheckListener($passwordHasher));

        // 注册登录限流监听器
        $loginRateLimiterConfig = $guardConfig->loginRateLimiterConfig();
        $rateLimiter = $container->get(LoginRateLimiterFactory::class)->create($loginRateLimiterConfig);
        $eventDispatcher->addSubscriber(new LoginRateLimitListener($rateLimiter, $container->get(IpResolver::class)));

        $userProvider = $container->get(UserProviderFactory::class)->create($guardConfig->userProviderConfig());

        $authenticators = [];
        foreach ($guardConfig->authenticatorConfigCollection() as $authenticatorConfig) {
            $authenticators[$authenticatorConfig->type()] = $container->get(AuthenticatorFactory::class)->create(
                $authenticatorConfig,
                $userProvider,
                $eventDispatcher
            );
        }

        // 注册自定义监听器
        foreach ($guardConfig->listenerConfigCollection() as $listenerConfig) {
            $listener = $container->make($listenerConfig->class(), $listenerConfig->args());
            if (!$listener instanceof EventSubscriberInterface) {
                throw new \InvalidArgumentException(sprintf('Listener "%s" must implement EventSubscriberInterface.', $listenerConfig->class()));
            }

            $eventDispatcher->addSubscriber($listener);
        }

        return new Guard(
            name: $guardName,
            requestMatcher: $requestMatcher,
            tokenStorage: $tokenStorage,
            tokenContext: $container->get(TokenContextInterface::class),
            authenticators: $authenticators,
            unauthenticatedHandler: $unauthenticatedHandler,
            authorizationChecker: $authorizationChecker,
            accessDeniedHandler: $accessDeniedHandler,
            eventDispatcher: $eventDispatcher,
        );
    }
}
