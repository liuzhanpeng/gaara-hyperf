<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Encryptor\EncryptorInterface;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Exception\InvalidSignatureException;
use GaaraHyperf\Exception\SignatureExpiredException;
use GaaraHyperf\Exception\UsedNonceException;
use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\User\PasswordAwareUserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;
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
     * @param EncryptorInterface|null $encryptor
     * @param array $options
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(
        private UserProviderInterface $userProvider,
        private CacheInterface $cache,
        private array $options,
        private ?EncryptorInterface $encryptor,
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
            throw new InvalidCredentialsException('Missing required authentication headers');
        }

        if ($this->options['nonce_enabled'] && empty($nonce)) {
            throw new InvalidCredentialsException(
                message: 'Missing required nonce header',
                userIdentifier: $apiKey,
            );
        }

        $now = time();
        $ts = (int) $timestamp;
        $skew = 300; // 允许 5 分钟的时钟偏差
        if ($ts < ($now - $this->options['ttl']) || $ts > ($now + $skew)) {
            throw new SignatureExpiredException(
                message: 'Request timestamp is invalid or expired',
                timestamp: $ts,
                currentTime: $now,
                userIdentifier: $apiKey,
            );
        }

        // 防止重放攻击
        if ($this->options['nonce_enabled']) {
            $cacheKey = sprintf('%s:%s', $this->options['nonce_cache_prefix'], md5($apiKey . $nonce));
            if ($this->cache->has($cacheKey)) {
                throw new UsedNonceException(
                    message: 'Nonce has already been used',
                    nonce: $nonce,
                    userIdentifier: $apiKey,
                );
            }

            $this->cache->set($cacheKey, true, $this->options['ttl']);
        }

        $user = $this->userProvider->findByIdentifier($apiKey);
        if (is_null($user)) {
            throw new UserNotFoundException(
                message: 'Invalid API key',
                userIdentifier: $apiKey,
            );
        }

        if (!$user instanceof PasswordAwareUserInterface) {
            throw new AuthenticationException(
                message: 'User must implement PasswordAwareUserInterface',
                userIdentifier: $apiKey,
            );
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
            throw new InvalidSignatureException(
                message: 'Invalid request signature',
                userIdentifier: $apiKey,
            );
        }

        return new Passport(
            $apiKey,
            fn() => $user,
        );
    }

    /**
     * @inheritDoc
     * @override
     */
    public function onAuthenticationFailure(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($guardName, $request, $exception, $passport);
        }

        $response = new \Hyperf\HttpMessage\Server\Response();
        return $response->withStatus(401)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream($exception->getMessage()));
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
