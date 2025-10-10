<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\User\SecretAwareUserInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * API签名认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class APISignatureAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private array $options,
    ) {
        $this->options = array_merge([
            'api_key_param' => 'X-API-KEY',
            'signature_param' => 'X-Signature',
            'timestamp_param' => 'X-Timestamp',
            'nonce_param' => 'X-Nonce',
            'ttl' => 60,
            'algo' => 'sha256',
        ], $this->options);
    }

    public function supports(\Psr\Http\Message\ServerRequestInterface $request): bool
    {
        return !empty($request->getHeaderLine($this->options['api_key_param'])) &&
            !empty($request->getHeaderLine($this->options['signature_param'])) &&
            !empty($request->getHeaderLine($this->options['timestamp_param'])) &&
            !empty($request->getHeaderLine($this->options['nonce_param']));
    }

    public function authenticate(ServerRequestInterface $request, string $guardName): Passport
    {
        $apiKey = $request->getHeaderLine($this->options['api_key_param']);
        $signature = $request->getHeaderLine($this->options['signature_param']);
        $timestamp = $request->getHeaderLine($this->options['timestamp_param']);
        $nonce = $request->getHeaderLine($this->options['nonce_param']);
        if (empty($apiKey) || empty($signature) || empty($timestamp) || empty($nonce)) {
            throw new AuthenticationException('Missing required authentication headers', $apiKey);
        }

        if ($timestamp + $this->options['ttl'] < time()) {
            throw new AuthenticationException('Request signature has expired', $apiKey);
        }

        $user = $this->userProvider->findByIdentifier($apiKey);
        if (is_null($user)) {
            throw new AuthenticationException('Invalid API key', $apiKey);
        }

        if (!$user instanceof SecretAwareUserInterface) {
            throw new AuthenticationException('User must implement SecretAwareUserInterface', $apiKey);
        }

        $params = array_merge($request->getQueryParams(), $request->getParsedBody() ?? []);
        $params = array_merge($params, [
            $this->options['api_key_param'] => $apiKey,
            $this->options['timestamp_param'] => $timestamp,
            $this->options['nonce_param'] => $nonce,
        ]);
        ksort($params);
        $paramStr = http_build_query($params);

        $computedSignature = hash_hmac($this->options['algo'], $paramStr, $user->getSecret());
        if (!hash_equals($computedSignature, $signature)) {
            throw new AuthenticationException('Invalid request signature', $apiKey);
        }

        return new Passport(
            $guardName,
            $apiKey,
            fn() => $user,
            []
        );
    }

    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new AuthenticatedToken($guardName, $passport->getUser());
    }

    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        return null;
    }

    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        throw $exception;
    }

    public function isInteractive(): bool
    {
        return false;
    }
}
