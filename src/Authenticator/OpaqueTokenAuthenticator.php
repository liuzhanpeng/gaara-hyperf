<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Lzpeng\HyperfAuthGuard\OpaqueToken\OpaqueTokenIssuerInterface;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 不透明令牌认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class OpaqueTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private OpaqueTokenIssuerInterface $tokenIssuer,
        private array $options,
    ) {
        $this->options = array_merge([
            'header_param' => 'Authorization',
            'token_type' => 'Bearer',
        ], $this->options);
    }

    /**
     * @inheritDoc
     */
    public function supports(RequestInterface $request): bool
    {
        return $this->extractAccessToken($request) !== null;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(RequestInterface $request, string $guardName): Passport
    {
        $accessToken = $this->extractAccessToken($request);

        $token = $this->tokenIssuer->resolve($accessToken);
        if (is_null($token)) {
            throw new UnauthenticatedException('Token is invalid.');
        }

        return new Passport(
            $guardName,
            $token->getUser()->getIdentifier(),
            fn() => $token->getUser(),
            []
        );
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(RequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(RequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        throw $exception;
    }

    /**
     * 提取AccessToken
     *
     * @param RequestInterface $request
     * @return string|null
     */
    public function extractAccessToken(RequestInterface $request): ?string
    {
        if (!$request->hasHeader($this->options['header_param']) || !\is_string($header = $request->getHeaderLine($this->options['header_param']))) {
            return null;
        }

        $regex = \sprintf(
            '/^%s([a-zA-Z0-9\-_\+~\/\.]+=*)$/',
            '' === $this->options['token_type'] ? '' : preg_quote($this->options['token_type']) . '\s+'
        );

        if (preg_match($regex, $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
