<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorFactory;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\OpaqueTokenAuthenticator;
use GaaraHyperf\EventListener\OpaqueTokenRevokeLogoutListener;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * OpaqueToken认证器构建器
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        $options = array_merge([
            'token_manager' => 'default',
            'token_extractor' => [
                'type' => 'header',
                'param_name' => 'Authorization',
                'param_type' => 'Bearer',
            ],
        ], $options);

        $opaqueTokenManager = $this->container->get(OpaqueTokenManagerResolverInterface::class)->resolve($options['token_manager']);
        $accessTokenExtractorFactory = $this->container->get(AccessTokenExtractorFactory::class);
        $accessTokenExtractor = $accessTokenExtractorFactory->create($options['token_extractor']);
        $eventDispatcher->addSubscriber(new OpaqueTokenRevokeLogoutListener($opaqueTokenManager, $accessTokenExtractor));

        return new OpaqueTokenAuthenticator(
            userProvider: $userProvider,
            opaqueTokenManager: $opaqueTokenManager,
            accessTokenExtractor: $accessTokenExtractor,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
