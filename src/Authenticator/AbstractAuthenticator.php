<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\Token\TokenInterface;
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
     * @param string $guardName
     * @param ServerRequestInterface $request
     * @param TokenInterface $token
     * @param Passport $passport
     * @return ResponseInterface|null
     */
    public function onAuthenticationSuccess(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($guardName, $request, $token, $passport);
        }

        return null;
    }

    /**
     * 认证失败处理
     *
     * @param string $guardName
     * @param ServerRequestInterface $request
     * @param AuthenticationException $exception
     * @param Passport|null $passport
     * @return ResponseInterface|null
     */
    public function onAuthenticationFailure(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($guardName, $request, $exception, $passport);
        }

        throw $exception;
    }
}
