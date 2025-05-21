<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Factory;

use Hyperf\Contract\SessionInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\FormLogAuthenticator;
use Lzpeng\HyperfAuthGuard\EventListener\CsrfTokenBadgeCheckListener;
use Lzpeng\HyperfAuthGuard\ServiceFactory\CsrfTokenManagerFactory;

class FormLoginAuthenticatorFactory extends AbstractAuthenticatorFactory
{
    public function create(array $options, string $userProviderId, string $eventDispatcherId): AuthenticatorInterface
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
}
