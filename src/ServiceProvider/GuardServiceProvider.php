<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorFactory;
use Lzpeng\HyperfAuthGuard\Config\ConfigLoaderInterface;
use Lzpeng\HyperfAuthGuard\Config\GuardConfig;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\EventListener\PasswordBadgeCheckListener;
use Lzpeng\HyperfAuthGuard\Guard;
use Lzpeng\HyperfAuthGuard\GuardInterface;
use Lzpeng\HyperfAuthGuard\GuardResolver;
use Lzpeng\HyperfAuthGuard\GuardResolverInterface;
use Lzpeng\HyperfAuthGuard\PasswordHasher\PasswordHasherResolverInterface;
use Lzpeng\HyperfAuthGuard\RequestMatcher\RequestMatcher;
use Lzpeng\HyperfAuthGuard\Token\TokenContext;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageFactory;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerFactory;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * 认证守卫服务提供者
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
            return new TokenContext('auth');
        });

        $config = $container->get(ConfigLoaderInterface::class)->load();

        $guardMap = [];
        foreach ($config->guardConfigCollection() as $guardName => $guardConfig) {
            $guardMap[$guardName] = sprintf('%s.%s', Constants::GUARD_PREFIX, $guardName);

            $container->define($guardMap[$guardName], function () use ($container, $guardName, $guardConfig) {
                return $this->createGuard($container, $guardName, $guardConfig);
            });
        }

        $container->set(GuardResolverInterface::class, new GuardResolver($container, $guardMap));
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
        $requestMatcher = new RequestMatcher($requestMatcherConfig->pattern(), $requestMatcherConfig->logoutPath(), $requestMatcherConfig->exclusions());

        $tokenStorage = $container->get(TokenStorageFactory::class)->create($guardConfig->tokenStorageConfig());
        $unauthenticatedHandler = $container->get(UnauthenticatedHandlerFactory::class)->create($guardConfig->unauthenticatedHandlerConfig());

        $authorizationCheckerConfig = $guardConfig->authorizationCheckerConfig();
        $authorizationChecker = $container->make($authorizationCheckerConfig->class(), $authorizationCheckerConfig->args());

        $accessDeniedHandlerConfig = $guardConfig->accessDeniedHandlerConfig();
        $accessDeniedHandler = $container->make($accessDeniedHandlerConfig->class(), $accessDeniedHandlerConfig->args());

        $eventDispatcher = new EventDispatcher();

        // 内置密码验证监听器
        $passwordHasherId = $container->get(PasswordHasherResolverInterface::class)->resolve($guardConfig->passwordHasherId());
        $eventDispatcher->addSubscriber(new PasswordBadgeCheckListener($passwordHasherId));

        // 注册自定义监听器
        foreach ($guardConfig->listenerConfigCollection() as $listenerConfig) {
            $listener = $container->make($listenerConfig->class(), $listenerConfig->args());
            if (!$listener instanceof \Symfony\Component\EventDispatcher\EventSubscriberInterface) {
                throw new \InvalidArgumentException(sprintf('Listener "%s" must implement EventSubscriberInterface.', $listenerConfig->class()));
            }

            $eventDispatcher->addSubscriber($listener);
        }

        $authenticators = [];
        $userProvider = $container->get(UserProviderFactory::class)->create($guardConfig->userProviderConfig());
        foreach ($guardConfig->authenticatorConfigCollection() as $authenticatorConfig) {
            $authenticators[$authenticatorConfig->type()] = $container->get(AuthenticatorFactory::class)->create(
                $authenticatorConfig,
                $userProvider,
                $eventDispatcher
            );
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
