<?php

declare(strict_types=1);

namespace GaaraHyperf\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Authenticator\AuthenticatorFactory;
use GaaraHyperf\Authenticator\Builder\APIKeyAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\FormLoginAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\HmacAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\JsonLoginAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\OpaqueTokenAuthenticatorBuilder;
use GaaraHyperf\Authenticator\Builder\X509AuthenticatorBuilder;

/**
 * 内置认证器服务提供者
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class BuiltInAuthenticatorServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        /**
         * @var AuthenticatorFactory $authenticatorFactory
         */
        $authenticatorFactory = $container->get(AuthenticatorFactory::class);
        $authenticatorFactory->registerBuilder('form_login', FormLoginAuthenticatorBuilder::class);
        $authenticatorFactory->registerBuilder('json_login', JsonLoginAuthenticatorBuilder::class);
        $authenticatorFactory->registerBuilder('api_key', APIKeyAuthenticatorBuilder::class);
        $authenticatorFactory->registerBuilder('hmac', HmacAuthenticatorBuilder::class);
        $authenticatorFactory->registerBuilder('opaque_token', OpaqueTokenAuthenticatorBuilder::class);
        $authenticatorFactory->registerBuilder('x509', X509AuthenticatorBuilder::class);
    }
}
