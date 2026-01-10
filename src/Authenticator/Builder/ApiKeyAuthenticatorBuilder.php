<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use GaaraHyperf\Authenticator\APIKeyAuthenticator;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
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
        return new APIKeyAuthenticator(
            apiKeyField: $options['api_key_field'] ?? 'X-API-KEY',
            userProvider: $userProvider,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
