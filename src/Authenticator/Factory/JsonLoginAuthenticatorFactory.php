<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Factory;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\JsonLoginAuthenticator;
use Lzpeng\HyperfAuthGuard\Util\Util;

class JsonLoginAuthenticatorFactory extends AbstractAuthenticatorFactory
{
    public function create(array $options, string $userProviderId, string $eventDispatcherId): AuthenticatorInterface
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
}
