<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\OpaqueTokenAuthenticator;
use GaaraHyperf\EventListener\OpaqueTokenRevokeLogoutListener;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenProcessorResolverInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * OpaqueToken认证器构建器.
 */
class OpaqueTokenAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        $opaqueTokenProcessor = $this->container->get(OpaqueTokenProcessorResolverInterface::class)->resolve($options['token_manager'] ?? 'default');

        $eventDispatcher->addSubscriber(new OpaqueTokenRevokeLogoutListener($opaqueTokenProcessor));

        return new OpaqueTokenAuthenticator(
            userProvider: $userProvider,
            opaqueTokenProcessor: $opaqueTokenProcessor,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
