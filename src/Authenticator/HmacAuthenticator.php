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
class HmacAuthenticator extends AbstractAuthenticator
{
    /**
     * @param string $apiKeyField
     * @param string $signatureField
     * @param string $timestampField
     * @param bool $nonceEnabled
     * @param string $nonceField
     * @param string $nonceCachePrefix
     * @param integer $ttl
     * @param integer $leeway
     * @param string $algo
     * @param UserProviderInterface $userProvider
     * @param CacheInterface $cache
     * @param EncryptorInterface|null $encryptor
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(
        private string $apiKeyField,
        private string $signatureField,
        private string $timestampField,
        private bool $nonceEnabled,
        private string $nonceField,
        private string $nonceCachePrefix,
        private int $ttl,
        private int $leeway,
        private string $algo,
        private UserProviderInterface $userProvider,
        private CacheInterface $cache,
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
        return !empty($request->getHeaderLine($this->apiKeyField))
            && !empty($request->getHeaderLine($this->signatureField))
            && !empty($request->getHeaderLine($this->timestampField));
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $apiKey = $request->getHeaderLine($this->apiKeyField);
        $signature = $request->getHeaderLine($this->signatureField);
        $timestamp = $request->getHeaderLine($this->timestampField);
        $nonce = $request->getHeaderLine($this->nonceField);
        if (empty($apiKey) || empty($signature) || empty($timestamp)) {
            throw new InvalidCredentialsException('Missing required authentication headers');
        }

        if ($this->nonceEnabled && empty($nonce)) {
            throw new InvalidCredentialsException(
                message: 'Missing required nonce header',
                userIdentifier: $apiKey,
            );
        }

        $now = time();
        $ts = (int) $timestamp;
        $leeway = (int) $this->leeway;
        if ($ts < ($now - $this->ttl) || $ts > ($now + $leeway)) {
            throw new SignatureExpiredException(
                message: 'Request timestamp is invalid or expired',
                timestamp: $ts,
                currentTime: $now,
                userIdentifier: $apiKey,
            );
        }

        // 防止重放攻击
        if ($this->nonceEnabled) {
            $cacheKey = sprintf('%s:%s', $this->nonceCachePrefix, md5($apiKey . $nonce));
            if ($this->cache->has($cacheKey)) {
                throw new UsedNonceException(
                    message: 'Nonce has already been used',
                    nonce: $nonce,
                    userIdentifier: $apiKey,
                );
            }

            $this->cache->set($cacheKey, true, $this->ttl);
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
        if ($this->nonceEnabled) {
            $parts[] = $nonce;
        }
        $parts[] = $bodyDigest;
        $signStr = implode("\n", $parts);

        $secret = $user->getPassword();
        if (!is_null($this->encryptor)) {
            $secret = $this->encryptor->decrypt($secret);
        }

        // 验签
        $computedSignature = hash_hmac($this->algo, $signStr, $secret);
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
