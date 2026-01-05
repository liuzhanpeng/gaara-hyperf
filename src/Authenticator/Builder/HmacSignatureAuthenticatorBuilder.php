<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use GaaraHyperf\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\HmacSignatureAuthenticator;
use GaaraHyperf\Constants;
use GaaraHyperf\Encryptor\EncryptorFactory;
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
            'skew' => 300,
            'algo' => 'sha256',
            'secret_encrypto_enabled' => false,
        ], $options);

        $options['nonce_cache_prefix'] = sprintf('%s:hmac_nonce:%s', Constants::__PREFIX, $options['nonce_cache_prefix'] ?? 'default');

        $encryptor = null;
        if ($options['secret_encrypto_enabled']) {
            $encryptorFactory = $this->container->get(EncryptorFactory::class);
            $encryptor = $encryptorFactory->create($options['secret_encryptor'] ?? []);
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
