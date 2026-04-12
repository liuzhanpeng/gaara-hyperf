<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use GaaraHyperf\Authentication\AuthenticationTrustDeciderInterface;
use GaaraHyperf\Authentication\DefaultAuthenticationTrustDecider;
use GaaraHyperf\Authenticator\AuthenticatorFactory;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\Config\CustomConfig;
use GaaraHyperf\Config\GuardConfig;
use GaaraHyperf\Constants;
use GaaraHyperf\EventListener\PasswordBadgeCheckListener;
use GaaraHyperf\Guard;
use GaaraHyperf\GuardInterface;
use GaaraHyperf\GuardResolver;
use GaaraHyperf\PasswordHasher\PasswordHasherResolverInterface;
use GaaraHyperf\RequestMatcher\RequestMatcherFactory;
use GaaraHyperf\Token\TokenContext;
use GaaraHyperf\Token\TokenContextInterface;
use GaaraHyperf\TokenStorage\TokenStorageFactory;
use GaaraHyperf\UnauthenticatedHandler\UnauthenticatedHandlerFactory;
use GaaraHyperf\UserProvider\UserProviderFactory;
use Hyperf\Contract\ContainerInterface;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 认证守卫服务提供者.
 *
 * 核心提供器，负责在容器中注册所有的认证守卫
 */
class GuardServiceProvider implements ServiceProviderInterface
{
    /**
     * 注册服务
     */
    public function register(ContainerInterface $container): void
    {
        // 注册内置的令牌上下文
        $container->define(TokenContextInterface::class, new TokenContext(Constants::TOKEN_CONTEXT_PREFIX));

        $config = $container->get(ConfigLoaderInterface::class)->load();
        $defaultAuthenticationTrustDeciderConfig = new CustomConfig(DefaultAuthenticationTrustDecider::class, []);

        $container->define(AuthenticationTrustDeciderInterface::class, new DefaultAuthenticationTrustDecider());

        $factories = [];
        foreach ($config->guardConfigCollection() as $guardName => $guardConfig) {
            $factories[$guardName] = fn () => $this->createGuard(
                $container,
                $guardName,
                $guardConfig,
                $defaultAuthenticationTrustDeciderConfig
            );
        }

        $container->set(GuardResolver::class, new GuardResolver($factories));
    }

    /**
     * 创建一个认证守卫实例.
     */
    private function createGuard(ContainerInterface $container, string $guardName, GuardConfig $guardConfig, CustomConfig $defaultAuthenticationTrustDeciderConfig): GuardInterface
    {
        $requestMatcher = $container->get(RequestMatcherFactory::class)->create($guardConfig->requestMatcherConfig());
        $tokenStorage = $container->get(TokenStorageFactory::class)->create($guardConfig->tokenStorageConfig());
        $unauthenticatedHandler = $container->get(UnauthenticatedHandlerFactory::class)->create($guardConfig->unauthenticatedHandlerConfig());

        $authorizationCheckerConfig = $guardConfig->authorizationCheckerConfig();
        $authorizationChecker = $container->make($authorizationCheckerConfig->class(), $authorizationCheckerConfig->params());

        $accessDeniedHandlerConfig = $guardConfig->accessDeniedHandlerConfig();
        $accessDeniedHandler = $container->make($accessDeniedHandlerConfig->class(), $accessDeniedHandlerConfig->params());

        $authenticationTrustDeciderConfig = $guardConfig->authenticationTrustDeciderConfig() ?? $defaultAuthenticationTrustDeciderConfig;
        $authenticationTrustDecider = $container->make(
            $authenticationTrustDeciderConfig->class(),
            $authenticationTrustDeciderConfig->params()
        );
        if (! $authenticationTrustDecider instanceof AuthenticationTrustDeciderInterface) {
            throw new InvalidArgumentException(sprintf(
                'Authentication trust decider "%s" must implement %s.',
                $authenticationTrustDeciderConfig->class(),
                AuthenticationTrustDeciderInterface::class
            ));
        }

        $eventDispatcher = new EventDispatcher();

        // 注册内置密码验证监听器
        $passwordHasher = $container->get(PasswordHasherResolverInterface::class)->resolve($guardConfig->passwordHasherId());
        $eventDispatcher->addSubscriber(new PasswordBadgeCheckListener($passwordHasher));

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
            $listener = $container->make($listenerConfig->class(), $listenerConfig->params());
            if (! $listener instanceof EventSubscriberInterface) {
                throw new InvalidArgumentException(sprintf('Listener "%s" must implement EventSubscriberInterface.', $listenerConfig->class()));
            }

            $eventDispatcher->addSubscriber($listener);
        }

        return new Guard(
            name: $guardName,
            requestMatcher: $requestMatcher,
            tokenStorage: $tokenStorage,
            tokenContext: $container->get(TokenContextInterface::class),
            userProvider: $userProvider,
            authenticators: $authenticators,
            unauthenticatedHandler: $unauthenticatedHandler,
            authorizationChecker: $authorizationChecker,
            accessDeniedHandler: $accessDeniedHandler,
            authenticationTrustDecider: $authenticationTrustDecider,
            eventDispatcher: $eventDispatcher,
        );
    }
}
