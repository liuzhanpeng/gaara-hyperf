<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\X509Authenticator;

class X509AuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        $options = array_merge([
            'email_param' => 'SSL_CLIENT_S_DN_Email',
            'common_name_param' => 'SSL_CLIENT_S_DN_CN',
        ], $options);

        return new X509Authenticator(
            userProvider: $userProvider,
            successHandler: $this->createSuccessHandler($options, $eventDispatcher),
            failureHandler: $this->createFailureHandler($options, $eventDispatcher),
            options: $options,
        );
    }
}
