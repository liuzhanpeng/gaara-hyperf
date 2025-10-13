<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Lzpeng\HyperfAuthGuard\Authenticator\APIKeyAuthenticator;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * API Key认证器构建器
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class APIKeyAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        $options = array_merge([
            'api_key_param' => 'X-API-KEY',
        ], $options);

        $successHandler = $this->createSuccessHandler($options);
        $failureHandler = $this->createFailureHandler($options);

        return new APIKeyAuthenticator(
            userProvider: $userProvider,
            options: $options,
            successHandler: $successHandler,
            failureHandler: $failureHandler,
        );
    }
}
