<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Lzpeng\HyperfAuthGuard\Authenticator\ApiKeyAuthenticator;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ApiKeyAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        if (!isset($options['check_path'])) {
            throw new \InvalidArgumentException('The "check_path" option must be set.');
        }

        $options = array_merge([
            'api_key_param' => 'X-API-KEY',
        ], $options);

        $successHandler = $this->createSuccessHandler($options);
        $failureHandler = $this->createFailureHandler($options);

        return new ApiKeyAuthenticator(
            userProvider: $userProvider,
            successHandler: $successHandler,
            failureHandler: $failureHandler,
            options: $options,
        );
    }
}
