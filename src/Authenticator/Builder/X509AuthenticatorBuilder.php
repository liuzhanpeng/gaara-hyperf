<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\X509Authenticator;

/**
 * X509证书认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class X509AuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        $options = array_merge([
            'ssl_client_s_dn_param' => 'SSL_CLIENT_S_DN',
            'identifier_field' => 'email',
        ], $options);

        return new X509Authenticator(
            userProvider: $userProvider,
            successHandler: $this->createSuccessHandler($options, $eventDispatcher),
            failureHandler: $this->createFailureHandler($options, $eventDispatcher),
            options: $options,
        );
    }
}
