<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Factory;

use Lzpeng\HyperfAuthGuard\Authenticator\ApiKeyAuthenticator;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;

class ApiKeyAuthenticatorFactory extends AbstractAuthenticatorFactory
{
    public function create(array $options, string $userProviderId, string $eventDispatcherId): AuthenticatorInterface
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
}
