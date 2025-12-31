<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\User\PasswordAwareUserInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Lzpeng\HyperfAuthGuard\Utils\Encryptor;
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
            throw new AuthenticationException($apiKey, 'Missing required authentication headers');
        }

        if ($this->options['nonce_enabled'] && empty($nonce)) {
            throw new AuthenticationException($apiKey, 'Missing required nonce header');
        }

        $now = time();
        $ts = (int) $timestamp;
        $skew = 300; // 允许 5 分钟的时钟偏差
        if ($ts < ($now - $this->options['ttl']) || $ts > ($now + $skew)) {
            throw new AuthenticationException($apiKey, 'Request timestamp is invalid or expired');
        }

        // 防止重放攻击
        if ($this->options['nonce_enabled']) {
            $cacheKey = sprintf('%s:%s', $this->options['nonce_cache_prefix'], md5($apiKey . $nonce));
            if ($this->cache->has($cacheKey)) {
                throw new AuthenticationException($apiKey, 'Nonce has already been used');
            }

            $this->cache->set($cacheKey, true, $this->options['ttl']);
        }

        $user = $this->userProvider->findByIdentifier($apiKey);
        if (is_null($user)) {
            throw new AuthenticationException($apiKey, 'Invalid API key');
        }

        if (!$user instanceof PasswordAwareUserInterface) {
            throw new AuthenticationException($apiKey, 'User must implement PasswordAwareUserInterface');
        }

        // 构建待签名字符串
        // 结构: METHOD \n PATH \n QUERY \n APIKEY \n TIMESTAMP [\n NONCE] \n BODY_HASH
        $queryParams = $request->getQueryParams();
        ksort($queryParams);
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $bodyContent = $request->getBody()->getContents();
        $request->getBody()->rewind();
        $bodyDigest = hash('sha256', $bodyContent);

        $path = $request->getUri()->getPath();
        if ($path === '') {
            $path = '/';
        }

        $parts = [
            strtoupper($request->getMethod()),
            $path,
            $queryString,
            $apiKey,
            $timestamp,
        ];
        if ($this->options['nonce_enabled']) {
            $parts[] = $nonce;
        }
        $parts[] = $bodyDigest;
        $signStr = implode("\n", $parts);

        $secret = $user->getPassword();
        if (!is_null($this->encryptor)) {
            $secret = $this->encryptor->decrypt($secret);
        }

        // 验签
        $computedSignature = base64_encode(hash_hmac($this->options['algo'], $signStr, $secret, true));
        if (!hash_equals($computedSignature, $signature)) {
            throw new AuthenticationException($apiKey, 'Invalid request signature');
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
