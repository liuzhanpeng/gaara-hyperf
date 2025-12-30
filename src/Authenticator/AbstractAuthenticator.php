<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 抽象认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
abstract class AbstractAuthenticator implements AuthenticatorInterface
{
    /**
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(
        protected ?AuthenticationSuccessHandlerInterface $successHandler,
        protected ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {}

    /**
     * 创建认证令牌
     *
     * @param Passport $passport
     * @param string $guardName
     * @return TokenInterface
     */
    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new AuthenticatedToken($guardName, $passport->getUserIdentifier());
    }

    /**
     * 认证成功处理
     * 
     * @param ServerRequestInterface $request
     * @param TokenInterface $token
     * @return ResponseInterface|null
     */
    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        return null;
    }

    /**
     * 认证失败处理
     *
     * @param ServerRequestInterface $request
     * @param AuthenticationException $exception
     * @return ResponseInterface|null
     */
    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        throw $exception;
    }
}
