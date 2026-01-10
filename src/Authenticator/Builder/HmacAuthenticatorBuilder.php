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
        $options = array_replace_recursive([
            'api_key_field' => 'X-API-KEY',
            'signature_field' => 'X-SIGNATURE',
            'timestamp_field' => 'X-TIMESTAMP',
            'nonce_enabled' => true,
            'nonce_field' => 'X-NONCE',
            'nonce_cache_prefix' => 'default',
            'ttl' => 60,
            'leeway' => 300,
            'algo' => 'sha256',
            'secret_encrypto_enabled' => false,
        ], $options);

        $options['nonce_cache_prefix'] = sprintf('%s:hmac_nonce:%s', Constants::__PREFIX, $options['nonce_cache_prefix'] ?? 'default');

        $encryptor = null;
        if ($options['secret_encrypto_enabled']) {
            $encryptorFactory = $this->container->get(EncryptorFactory::class);
            $encryptor = $encryptorFactory->create($options['secret_encryptor'] ?? []);
        }

        return new HmacAuthenticator(
            userProvider: $userProvider,
            cache: $this->container->get(CacheInterface::class),
            options: $options,
            encryptor: $encryptor,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
