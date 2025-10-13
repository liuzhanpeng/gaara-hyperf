<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorFactory;
use Lzpeng\HyperfAuthGuard\Authenticator\Builder\APIKeyAuthenticatorBuilder;
use Lzpeng\HyperfAuthGuard\Authenticator\Builder\APISignatureAuthenticatorBuilder;
use Lzpeng\HyperfAuthGuard\Authenticator\Builder\FormLoginAuthenticatorBuilder;
use Lzpeng\HyperfAuthGuard\Authenticator\Builder\JsonLoginAuthenticatorBuilder;
use Lzpeng\HyperfAuthGuard\Authenticator\Builder\OpaqueTokenAuthenticatorBuilder;
use Lzpeng\HyperfAuthGuard\Authenticator\Builder\X509AuthenticatorBuilder;

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
        $authenticatorFactory->registerBuilder('api_signature', APISignatureAuthenticatorBuilder::class);
        $authenticatorFactory->registerBuilder('opaque_token', OpaqueTokenAuthenticatorBuilder::class);
        $authenticatorFactory->registerBuilder('x509', X509AuthenticatorBuilder::class);
    }
}
