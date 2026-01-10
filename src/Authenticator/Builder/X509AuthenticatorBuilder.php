<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use GaaraHyperf\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\X509Authenticator;

/**
 * X509证书认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class X509AuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        return new X509Authenticator(
            sslClientSDNField: $options['ssl_client_s_dn_field'] ?? 'SSL_CLIENT_S_DN',
            identifierField: $options['identifier_field'] ?? 'email',
            userProvider: $userProvider,
            successHandler: $this->createSuccessHandler($options, $eventDispatcher),
            failureHandler: $this->createFailureHandler($options, $eventDispatcher),
        );
    }
}
