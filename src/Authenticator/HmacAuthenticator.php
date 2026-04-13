<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Encryptor\EncryptorInterface;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Exception\InvalidSignatureException;
use GaaraHyperf\Exception\SignatureExpiredException;
use GaaraHyperf\Exception\UsedNonceException;
use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\User\PasswordAwareUserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * Hmac签名认证器.
 */
class HmacAuthenticator extends AbstractAuthenticator
{
    private const HASH_ALGO = 'sha256';

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

        if (! in_array($this->algo, hash_hmac_algos(), true)) {
            throw new InvalidArgumentException(sprintf('Unsupported hmac algorithm "%s".', $this->algo));
        }
    }

    public function supports(ServerRequestInterface $request): bool
    {
        return ! empty($request->getHeaderLine($this->apiKeyField))
            && ! empty($request->getHeaderLine($this->signatureField))
            && ! empty($request->getHeaderLine($this->timestampField));
    }

    public function authenticate(ServerRequestInterface $request): Passport
    {
        [$apiKey, $signature, $timestamp, $nonce] = $this->extractHeaders($request);

        $this->validateRequiredHeaders($apiKey, $signature, $timestamp);
        $this->validateNonceHeader($apiKey, $nonce);
        $this->validateTimestamp($apiKey, $timestamp);
        $this->validateAndCacheNonce($apiKey, $nonce);

        $user = $this->loadPasswordAwareUser($apiKey);
        $signatureData = $this->buildSignatureString($request, $apiKey, $timestamp, $nonce);
        $secret = $this->resolveUserSecret($user);

        $this->verifySignature($apiKey, $signatureData, $signature, $secret);

        return new Passport(
            $apiKey,
            fn () => $user,
        );
    }

    public function isInteractive(): bool
    {
        return false;
    }

    private function extractHeaders(ServerRequestInterface $request): array
    {
        return [
            $request->getHeaderLine($this->apiKeyField),
            $request->getHeaderLine($this->signatureField),
            $request->getHeaderLine($this->timestampField),
            $request->getHeaderLine($this->nonceField),
        ];
    }

    private function validateRequiredHeaders(string $apiKey, string $signature, string $timestamp): void
    {
        if (empty($apiKey) || empty($signature) || empty($timestamp)) {
            throw new InvalidCredentialsException('Missing required authentication headers');
        }
    }

    private function validateNonceHeader(string $apiKey, string $nonce): void
    {
        if ($this->nonceEnabled && empty($nonce)) {
            throw new InvalidCredentialsException(
                message: 'Missing required nonce header',
                userIdentifier: $apiKey,
            );
        }
    }

    private function validateTimestamp(string $apiKey, string $timestamp): void
    {
        $ts = filter_var($timestamp, FILTER_VALIDATE_INT);
        $now = time();
        $leeway = (int) $this->leeway;

        if ($ts === false || $ts < ($now - $this->ttl) || $ts > ($now + $leeway)) {
            throw new SignatureExpiredException(
                message: 'Request timestamp is invalid or expired',
                timestamp: (int) $timestamp,
                currentTime: $now,
                userIdentifier: $apiKey,
            );
        }
    }

    private function validateAndCacheNonce(string $apiKey, string $nonce): void
    {
        if (! $this->nonceEnabled) {
            return;
        }

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

    private function loadPasswordAwareUser(string $apiKey): PasswordAwareUserInterface
    {
        $user = $this->userProvider->findByIdentifier($apiKey);
        if (is_null($user)) {
            throw new UserNotFoundException(
                message: 'Invalid API key',
                userIdentifier: $apiKey,
            );
        }

        if (! $user instanceof PasswordAwareUserInterface) {
            throw new RuntimeException('User must implement PasswordAwareUserInterface');
        }

        return $user;
    }

    private function buildSignatureString(ServerRequestInterface $request, string $apiKey, string $timestamp, string $nonce): string
    {
        // 待签名内容: METHOD\nPATH\nQUERY\nAPIKEY\nTIMESTAMP[\nNONCE]\nBODY_HASH
        $queryParams = $request->getQueryParams();
        ksort($queryParams);
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $body = $request->getBody();
        $bodyContent = $body->getContents();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $bodyDigest = hash(self::HASH_ALGO, $bodyContent);

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

        return implode("\n", $parts);
    }

    private function resolveUserSecret(PasswordAwareUserInterface $user): string
    {
        $secret = $user->getPassword();
        if (is_null($this->encryptor)) {
            return $secret;
        }

        return $this->encryptor->decrypt($secret);
    }

    private function verifySignature(string $apiKey, string $signatureData, string $signature, string $secret): void
    {
        $computedSignature = hash_hmac($this->algo, $signatureData, $secret);
        if (! hash_equals($computedSignature, $signature)) {
            throw new InvalidSignatureException(
                message: 'Invalid request signature',
                userIdentifier: $apiKey,
            );
        }
    }
}
