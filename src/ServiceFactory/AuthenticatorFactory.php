<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceFactory;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\ApiKeyAuthenticator;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticationFailureHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticationSuccessHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\FormLogAuthenticator;
use Lzpeng\HyperfAuthGuard\Authenticator\JsonLoginAuthenticator;
use Lzpeng\HyperfAuthGuard\Authenticator\OpaqueTokenAuthenticator;
use Lzpeng\HyperfAuthGuard\Config\AuthenticatorConfig;
use Lzpeng\HyperfAuthGuard\EventListener\CsrfTokenBadgeCheckListener;
use Lzpeng\HyperfAuthGuard\EventListener\OpaqueTokenRevokeLogoutListener;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * 认证器工厂
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatorFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(
        AuthenticatorConfig $authenticatorConfig,
        string $userProviderId,
        string $eventDispatcherId,
    ): AuthenticatorInterface {
        $type = $authenticatorConfig->type();
        $options = $authenticatorConfig->options();

        switch ($type) {
            case 'form_login':
                return $this->createFormLoginAuthenticator($options, $userProviderId, $eventDispatcherId);
            case 'json_login':
                return $this->createJsonLoginAuthenticator($options, $userProviderId);
            case 'api_key':
                return $this->createApiKeyAuthenticator($options, $userProviderId);
            case 'opaque_token':
                return $this->createOpaqueTokenAuthenticator($options, $userProviderId);
            default:
                $authenticator = $this->container->make($type, $options);
                if (!$authenticator instanceof AuthenticatorInterface) {
                    throw new \LogicException(sprintf('Authenticator "%s" must implement AuthenticatorInterface', $type));
                }

                return $authenticator;
        }
    }

    /**
     * 创建FormLogin认证器
     *
     * @param array $options
     * @param string $userProviderId
     * @param string $eventDispatcherId
     * @return AuthenticatorInterface
     */
    private function createFormLoginAuthenticator(array $options, string $userProviderId, string $eventDispatcherId): AuthenticatorInterface
    {
        $successHandler = $this->createSuccessHandler($options);
        $failureHandler = $this->createFailureHandler($options);

        if ($options['csrf_enabled']) {
            if (!isset($options['csrf_token_manager'])) {
                $csrfTokenManagerConfig  = [
                    'default' => []
                ];
            } else {
                $csrfTokenManagerConfig = $options['csrf_token_manager'];
            }

            /**
             * @var EventDispatcher $eventDispatcher
             */
            $eventDispatcher = $this->container->get($eventDispatcherId);
            $eventDispatcher->addSubscriber(new CsrfTokenBadgeCheckListener(
                $this->container->get(CsrfTokenManagerFactory::class)->create($csrfTokenManagerConfig)
            ));
        }

        return new FormLogAuthenticator(
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            userProvider: $this->container->get($userProviderId),
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            session: $this->container->get(SessionInterface::class),
            options: $options,
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
        $successHandler = $this->createSuccessHandler($options);
        $failureHandler = $this->createFailureHandler($options);

        return new JsonLoginAuthenticator(
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            userProvider: $this->container->get($userProviderId),
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            util: $this->container->get(Util::class),
            options: $options,
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
        $successHandler = $this->createSuccessHandler($options);
        $failureHandler = $this->createFailureHandler($options);

        return new ApiKeyAuthenticator(
            userProvider: $this->container->get($userProviderId),
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            options: $options,
        );
    }

    /**
     * 创建OpaqueToken认证器
     *
     * @param array $options
     * @param string $userProviderId
     * @param string $eventDispatcherId
     * @return AuthenticatorInterface
     */
    private function createOpaqueTokenAuthenticator(array $options, string $userProviderId, string $eventDispatcherId): AuthenticatorInterface
    {
        $successHandler = $this->createSuccessHandler($options);
        $failureHandler = $this->createFailureHandler($options);

        if (!isset($options['token_issuer'])) {
            $tokenIssuerConfig = [
                'default' => [
                    'cache_prefix' => 'auth:opaque_token:',
                ],
            ];
        }

        $tokenIssuer = $this->container->get(OpaqueTokenIssuerFactory::class)->create($tokenIssuerConfig);

        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = $this->container->get($eventDispatcherId);
        $eventDispatcher->addSubscriber(new OpaqueTokenRevokeLogoutListener(
            opaqueTokenIssuer: $tokenIssuer,
            options: $options,
        ));

        return new OpaqueTokenAuthenticator(
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            tokenIssuer: $tokenIssuer,
            options: $options,
        );
    }


    /**
     * 创建SuccessHandler
     *
     * @param array $options
     * @return AuthenticationSuccessHandlerInterface|null
     */
    private function createSuccessHandler(array $options): ?AuthenticationSuccessHandlerInterface
    {
        if (!isset($options['success_handler'])) {
            return null;
        }

        $successHandlerOption = $options['success_handler'];
        unset($options['success_handler']);
        if (is_string($successHandlerOption)) {
            $successHandlerOption = [
                'class' => $successHandlerOption,
            ];
        }

        $successHandler = $this->container->make(
            $successHandlerOption['class'],
            $successHandlerOption['args'] ?? []
        );

        if (!$successHandler instanceof AuthenticationSuccessHandlerInterface) {
            throw new \InvalidArgumentException(sprintf('%s must implement %s', $successHandlerOption['class'], AuthenticationSuccessHandlerInterface::class));
        }

        return $successHandler;
    }

    /**
     * 创建FailureHandler
     *
     * @param array $options
     * @return AuthenticationFailureHandlerInterface|null
     */
    private function createFailureHandler(array $options): ?AuthenticationFailureHandlerInterface
    {
        if (!isset($options['failure_handler'])) {
            return null;
        }

        $failureHandlerOption = $options['failure_handler'];
        unset($options['failure_handler']);
        if (is_string($failureHandlerOption)) {
            $failureHandlerOption = [
                'class' => $failureHandlerOption,
            ];
        }

        $failureHandler = $this->container->make(
            $failureHandlerOption['class'],
            $failureHandlerOption['args'] ?? []
        );

        if (!$failureHandler instanceof AuthenticationFailureHandlerInterface) {
            throw new \InvalidArgumentException(sprintf('%s must implement %s', $failureHandlerOption['class'], AuthenticationFailureHandlerInterface::class));
        }

        return $failureHandler;
    }
}
