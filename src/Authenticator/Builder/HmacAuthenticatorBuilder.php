<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use GaaraHyperf\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\HmacAuthenticator;
use GaaraHyperf\Constants;
use GaaraHyperf\Encryptor\EncryptorFactory;
use Psr\SimpleCache\CacheInterface;

class HmacAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        $encryptor = null;
        if (isset($options['secret_encrypto_enabled']) && $options['secret_encrypto_enabled']) {
            $encryptorFactory = $this->container->get(EncryptorFactory::class);
            $encryptor = $encryptorFactory->create($options['secret_encryptor'] ?? []);
        }

        return new HmacAuthenticator(
            apiKeyField: $options['api_key_field'] ?? 'X-API-KEY',
            signatureField: $options['signature_field'] ?? 'X-SIGNATURE',
            timestampField: $options['timestamp_field'] ?? 'X-TIMESTAMP',
            nonceEnabled: $options['nonce_enabled'] ?? true,
            nonceField: $options['nonce_field'] ?? 'X-NONCE',
            nonceCachePrefix: sprintf('%s:hmac_nonce:%s', Constants::__PREFIX, $options['nonce_cache_prefix'] ?? 'default'),
            ttl: $options['ttl'] ?? 60,
            leeway: $options['leeway'] ?? 300,
            algo: $options['algo'] ?? 'sha256',
            userProvider: $userProvider,
            cache: $this->container->get(CacheInterface::class),
            encryptor: $encryptor,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
