<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\OpaqueTokenAuthenticator;
use Lzpeng\HyperfAuthGuard\EventListener\OpaqueTokenRevokeLogoutListener;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerResolverInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class OpaqueTokenAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        $options = array_merge([
            'header_param' => 'Authorization',
            'token_type' => 'Bearer',
            'token_issuer' => 'default',
            'token_refresh' => true,
        ], $options);


        $tokenIssuer = $this->container->get(OpaqueTokenIssuerResolverInterface::class)->resolve($options['token_issuer']);
        $eventDispatcher->addSubscriber(new OpaqueTokenRevokeLogoutListener(
            opaqueTokenIssuer: $tokenIssuer,
            options: $options,
        ));

        return new OpaqueTokenAuthenticator(
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
            tokenIssuer: $tokenIssuer,
            options: $options,
        );
    }
}
