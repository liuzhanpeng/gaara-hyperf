<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use ASCare\Shared\Infra\Encryptor;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\HmacSignatureAuthenticator;
use Lzpeng\HyperfAuthGuard\Constants;
use Psr\SimpleCache\CacheInterface;

class HmacSignatureAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        $options = array_merge([
            'api_key_param' => 'X-API-KEY',
            'signature_param' => 'X-SIGNATURE',
            'timestamp_param' => 'X-TIMESTAMP',
            'nonce_enabled' => true,
            'nonce_param' => 'X-NONCE',
            'nonce_cache_prefix' => 'default',
            'ttl' => 60,
            'algo' => 'sha256',
            'secret_crypto_enabled' => false,
        ], $options);

        $options['nonce_cache_prefix'] = sprintf('%s:hmac_nonce:%s', Constants::__PREFIX, $options['nonce_cache_prefix'] ?? 'default');

        $encryptor = null;
        if ($options['secret_crypto_enabled']) {
            if (empty($options['secret_crypto_key']) || empty($options['secret_crypto_algo'])) {
                throw new \InvalidArgumentException('Secret crypto key and algo must be provided when secret_crypto_enabled is true');
            }
            $encryptor = $this->container->make(Encryptor::class, [
                'key' => $options['secret_crypto_key'],
                'algo' => $options['secret_crypto_algo']
            ]);
        }

        return new HmacSignatureAuthenticator(
            userProvider: $userProvider,
            cache: $this->container->get(CacheInterface::class),
            options: $options,
            encryptor: $encryptor,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
