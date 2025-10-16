<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use ASCare\Shared\Infra\Encryptor;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\User\PasswordAwareUserInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Hmac签名认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class HmacSignatureAuthenticator extends AbstractAuthenticator
{
    /**
     * @param UserProviderInterface $userProvider
     * @param CacheInterface $cache
     * @param Encryptor|null $encryptor
     * @param array $options
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(
        private UserProviderInterface $userProvider,
        private CacheInterface $cache,
        private array $options,
        private ?Encryptor $encryptor,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return !empty($request->getHeaderLine($this->options['api_key_param']))
            && !empty($request->getHeaderLine($this->options['signature_param']))
            && !empty($request->getHeaderLine($this->options['timestamp_param']));
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $apiKey = $request->getHeaderLine($this->options['api_key_param']);
        $signature = $request->getHeaderLine($this->options['signature_param']);
        $timestamp = $request->getHeaderLine($this->options['timestamp_param']);
        $nonce = $request->getHeaderLine($this->options['nonce_param']);
        if (empty($apiKey) || empty($signature) || empty($timestamp)) {
            throw new AuthenticationException('Missing required authentication headers', $apiKey);
        }

        if ($this->options['nonce_enabled'] && empty($nonce)) {
            throw new AuthenticationException('Missing required nonce header', $apiKey);
        }

        if ($timestamp + $this->options['ttl'] < time()) {
            throw new AuthenticationException('Request signature has expired', $apiKey);
        }

        if ($this->options['nonce_enabled']) {
            $cacheKey = sprintf('%s:%s', $this->options['nonce_cache_prefix'], md5($apiKey . $nonce));
            if ($this->cache->has($cacheKey)) {
                throw new AuthenticationException('Nonce has already been used', $apiKey);
            }

            $this->cache->set($cacheKey, true, $this->options['ttl']);
        }

        $user = $this->userProvider->findByIdentifier($apiKey);
        if (is_null($user)) {
            throw new AuthenticationException('Invalid API key', $apiKey);
        }

        if (!$user instanceof PasswordAwareUserInterface) {
            throw new AuthenticationException('User must implement PasswordAwareUserInterface', $apiKey);
        }

        $params = array_merge($request->getQueryParams(), $request->getParsedBody() ?? []);
        $params = array_merge($params, [
            $this->options['api_key_param'] => $apiKey,
            $this->options['timestamp_param'] => $timestamp,
        ]);
        if ($this->options['nonce_enabled']) {
            $params[$this->options['nonce_param']] = $nonce;
        }
        ksort($params);
        $paramStr = http_build_query($params);

        $secret = $user->getPassword();
        if (!is_null($this->encryptor)) {
            $secret = $this->encryptor->decrypt($secret);
        }

        $computedSignature = hash_hmac($this->options['algo'], $paramStr, $secret);
        if (!hash_equals($computedSignature, $signature)) {
            throw new AuthenticationException('Invalid request signature', $apiKey);
        }

        return new Passport(
            $apiKey,
            fn() => $user,
        );
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
