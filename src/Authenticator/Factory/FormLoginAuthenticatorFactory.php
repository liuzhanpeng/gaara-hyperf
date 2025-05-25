<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Factory;

use Hyperf\Contract\SessionInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\FormLogAuthenticator;
use Lzpeng\HyperfAuthGuard\CsrfToken\CsrfTokenManagerFactory;
use Lzpeng\HyperfAuthGuard\EventListener\CsrfTokenBadgeCheckListener;

class FormLoginAuthenticatorFactory extends AbstractAuthenticatorFactory
{
    public function create(array $options, string $userProviderId, string $eventDispatcherId): AuthenticatorInterface
    {
        $successHandler = $this->createSuccessHandler($options);
        $failureHandler = $this->createFailureHandler($options);

        $options = array_merge([
            'target_path' => '/',
            'failure_path' => $options['check_path'],
            'redirect_enabled' => true,
            'redirect_param' => 'redirect_to',
            'username_param' => 'username',
            'password_param' => 'password',
            'csrf_enabled' => true,
            'csrf_id' => 'authenticate',
            'csrf_param' => '_csrf_token',
        ], $options);

        if ($options['csrf_enabled']) {
            if (!isset($options['csrf_token_manager'])) {
                $csrfTokenManagerConfig  = [
                    'default' => []
                ];
            } else {
                $csrfTokenManagerConfig = $options['csrf_token_manager'];
            }

            $csrfTokenManager = $this->container->get(CsrfTokenManagerFactory::class)->create($csrfTokenManagerConfig);

            /**
             * @var EventDispatcher $eventDispatcher
             */
            $eventDispatcher = $this->container->get($eventDispatcherId);
            $eventDispatcher->addSubscriber(new CsrfTokenBadgeCheckListener($csrfTokenManager));
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
}
