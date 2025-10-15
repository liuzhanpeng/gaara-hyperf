<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Lzpeng\HyperfAuthGuard\AccessTokenExtractor\AccessTokenExtractorResolverInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\OpaqueTokenAuthenticator;
use Lzpeng\HyperfAuthGuard\EventListener\OpaqueTokenRevokeLogoutListener;
use Lzpeng\HyperfAuthGuard\OpaqueTokenManager\OpaqueTokenManagerResolverInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
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
            'token_extractor' => 'default',
        ], $options);

        $opaqueTokenManager = $this->container->get(OpaqueTokenManagerResolverInterface::class)->resolve($options['token_manager']);
        $accessTokenExtractor = $this->container->get(AccessTokenExtractorResolverInterface::class)->resolve($options['token_extractor']);
        $eventDispatcher->addSubscriber(new OpaqueTokenRevokeLogoutListener($opaqueTokenManager, $accessTokenExtractor));

        return new OpaqueTokenAuthenticator(
            opaqueTokenManager: $opaqueTokenManager,
            accessTokenExtractor: $accessTokenExtractor,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
