<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\JsonLoginAuthenticator;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class JsonLoginAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        if (!isset($options['check_path'])) {
            throw new \InvalidArgumentException('The "check_path" option must be set.');
        }

        $options = array_merge([
            'username_param' => 'username',
            'password_param' => 'password',
        ], $options);

        $successHandler = $this->createSuccessHandler($options);
        $failureHandler = $this->createFailureHandler($options);

        return new JsonLoginAuthenticator(
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            userProvider: $userProvider,
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            options: $options,
        );
    }
}
