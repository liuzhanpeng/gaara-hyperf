<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Factory;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\OpaqueTokenAuthenticator;
use Lzpeng\HyperfAuthGuard\EventListener\OpaqueTokenRevokeLogoutListener;
use Lzpeng\HyperfAuthGuard\ServiceFactory\OpaqueTokenIssuerFactory;

class OpaqueTokenAuthenticatorFactory extends AbstractAuthenticatorFactory
{
    public function create(array $options, string $userProviderId, string $eventDispatcherId): AuthenticatorInterface
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
}
